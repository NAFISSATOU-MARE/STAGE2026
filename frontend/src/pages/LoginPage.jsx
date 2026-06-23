import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { LogoLogin } from '../components/LogoDGB'

export default function LoginPage() {
  const { login }  = useAuth()
  const navigate   = useNavigate()
  const [form,     setForm]     = useState({ email: '', password: '' })
  const [remember, setRemember] = useState(false)
  const [error,    setError]    = useState('')
  const [loading,  setLoading]  = useState(false)

  const handleSubmit = async e => {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      const agent = await login(form.email, form.password, remember)
      const isAnyAdmin = agent.role === 'ADMIN' || agent.role === 'ADMIN_DIRECTION'
      navigate(isAnyAdmin ? '/admin/dashboard' : '/dashboard')
    } catch (err) {
      setError(err.response?.data?.message || 'Identifiants incorrects.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-wrap">
      <div className="login-box">

        {/* Logo DGB officiel */}
        <div className="login-logo">
          <div className="login-logo-flag">
            <LogoLogin />
          </div>

          {/* Bandeau tricolore */}
          <div style={{
            height: 3, borderRadius: 2, margin: '14px 0 10px',
            background: 'linear-gradient(90deg, #1B7A3A 33%, #F0BC00 33% 66%, #C41230 66%)',
          }} />
          <p className="login-tagline">Gestion des congés et décisions</p>
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label>Adresse e-mail</label>
            <input
              type="email"
              className="form-control"
              value={form.email}
              onChange={e => setForm(f => ({ ...f, email: e.target.value }))}
              placeholder="prenom.nom@exemple.com"
              required autoFocus
            />
          </div>

          <div className="form-group">
            <label>Mot de passe</label>
            <input
              type="password"
              className="form-control"
              value={form.password}
              onChange={e => setForm(f => ({ ...f, password: e.target.value }))}
              placeholder="••••••••"
              required
            />
          </div>

          {/* Se souvenir de moi */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 18 }}>
            <input
              type="checkbox"
              id="remember"
              checked={remember}
              onChange={e => setRemember(e.target.checked)}
              style={{ width: 15, height: 15, cursor: 'pointer', accentColor: '#1B7A3A' }}
            />
            <label htmlFor="remember" style={{
              fontSize: 13, color: '#444', cursor: 'pointer',
              margin: 0, fontWeight: 500, textTransform: 'none', letterSpacing: 0,
            }}>
              Se souvenir de moi
            </label>
          </div>

          <button
            type="submit"
            className="btn btn-primary"
            style={{ width: '100%', justifyContent: 'center', height: 44 }}
            disabled={loading}
          >
            {loading ? <span className="spinner" /> : 'Se connecter'}
          </button>
        </form>

        <p style={{ textAlign: 'center', marginTop: 24, fontSize: 10.5, color: '#aaa', lineHeight: 1.6 }}>
          RÉPUBLIQUE DU SÉNÉGAL<br />
          Ministère de l'Économie, des Finances et du Plan
        </p>
      </div>
    </div>
  )
}
