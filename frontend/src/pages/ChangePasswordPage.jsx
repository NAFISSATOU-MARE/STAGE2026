import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import { useAuth } from '../context/AuthContext'

export default function ChangePasswordPage() {
  const { refreshUser, user } = useAuth()
  const navigate = useNavigate()
  const [form, setForm] = useState({
    current_password:          '',
    new_password:              '',
    new_password_confirmation: '',
  })
  const [error,   setError]   = useState('')
  const [loading, setLoading] = useState(false)

  const set = field => e => setForm(f => ({ ...f, [field]: e.target.value }))

  const handleSubmit = async e => {
    e.preventDefault()
    if (form.new_password !== form.new_password_confirmation) {
      setError('Les deux nouveaux mots de passe ne correspondent pas.')
      return
    }
    setError('')
    setLoading(true)
    try {
      await api.post('/api/change-password', form)
      await refreshUser()
      navigate(user?.role === 'ADMIN' ? '/admin/dashboard' : '/dashboard', { replace: true })
    } catch (err) {
      const errs = err.response?.data?.errors
      setError(errs
        ? Object.values(errs).flat().join(' · ')
        : err.response?.data?.message || 'Erreur lors du changement de mot de passe.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-wrap">
      <div className="login-box">
        {/* En-tête */}
        <div className="login-logo">
          <div style={{ fontSize: 36, marginBottom: 8 }}>🔐</div>
          <h2 style={{ fontSize: 18, fontWeight: 700, color: 'var(--primary)', marginBottom: 6 }}>
            Changement de mot de passe
          </h2>
          <p style={{ fontSize: 13, color: 'var(--gray)', lineHeight: 1.5 }}>
            Pour votre sécurité, veuillez définir un nouveau mot de passe
            avant d'accéder à l'application.
          </p>
          <div style={{
            height: 3, borderRadius: 2, marginTop: 14,
            background: 'linear-gradient(90deg, #1B7A3A 33%, #F0BC00 33% 66%, #C41230 66%)',
          }} />
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Mot de passe actuel</label>
            <input
              type="password"
              className="form-control"
              value={form.current_password}
              onChange={set('current_password')}
              required
              autoFocus
              placeholder="••••••••"
            />
          </div>

          <div className="form-group">
            <label>Nouveau mot de passe <span style={{ color: 'var(--gray)', fontWeight: 400 }}>(min. 8 caractères)</span></label>
            <input
              type="password"
              className="form-control"
              value={form.new_password}
              onChange={set('new_password')}
              required
              minLength={8}
              placeholder="••••••••"
            />
          </div>

          <div className="form-group">
            <label>Confirmer le nouveau mot de passe</label>
            <input
              type="password"
              className="form-control"
              value={form.new_password_confirmation}
              onChange={set('new_password_confirmation')}
              required
              placeholder="••••••••"
            />
          </div>

          <button
            type="submit"
            className="btn btn-primary"
            style={{ width: '100%', justifyContent: 'center', height: 42, marginTop: 4 }}
            disabled={loading}
          >
            {loading ? <span className="spinner" /> : 'Définir le nouveau mot de passe'}
          </button>
        </form>

        <p style={{ textAlign: 'center', marginTop: 20, fontSize: 11, color: '#aaa' }}>
          Connecté en tant que <strong style={{ color: '#555' }}>{user?.email}</strong>
        </p>
      </div>
    </div>
  )
}
