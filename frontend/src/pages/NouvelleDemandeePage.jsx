import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'

export default function NouvelleDemandeePage() {
  const { user, refreshUser } = useAuth()
  const navigate = useNavigate()

  const [solde,   setSolde]   = useState(null)
  const [form,    setForm]    = useState({
    type: '', date_debut: '', date_fin: '', motif: '', lieu_jouissance: ''
  })
  const [jours,   setJours]   = useState(0)
  const [error,   setError]   = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    api.get('/api/solde').then(r => {
      setSolde(r.data)
      // Pré-sélectionner le type selon le profil
      if (user?.profil === 'CONTRACTUEL') {
        setForm(f => ({ ...f, type: 'CONGE' }))
      }
    })
  }, [user])

  // Calcul automatique du nombre de jours
  useEffect(() => {
    if (form.date_debut && form.date_fin) {
      const d1 = new Date(form.date_debut)
      const d2 = new Date(form.date_fin)
      const diff = Math.floor((d2 - d1) / 86400000) + 1
      setJours(diff > 0 ? diff : 0)
    } else {
      setJours(0)
    }
  }, [form.date_debut, form.date_fin])

  const handleSubmit = async e => {
    e.preventDefault()
    setError('')
    if (jours <= 0) { setError('La date de fin doit être après la date de début.'); return }
    setLoading(true)
    try {
      const r = await api.post('/api/demandes', { ...form, lieu_jouissance: form.lieu_jouissance || undefined })
      await refreshUser()
      navigate(`/demandes/${r.data.id}`)
    } catch (err) {
      setError(err.response?.data?.message || 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  const peutSoumettreDecision = user?.profil === 'AGENT_ETAT'
  const peutSoumettreConge    = solde?.peut_soumettre_conge

  const today = new Date().toISOString().split('T')[0]

  return (
    <>
      <div className="page-header">
        <div className="page-title">Nouvelle demande</div>
      </div>

      <div className="card" style={{ maxWidth: 720 }}>
        {/* ── Informations pré-remplies ── */}
        <div className="card-title">Informations de l'agent (pré-remplies)</div>
        <div className="form-grid">
          <div className="form-group">
            <label>Prénom</label>
            <input className="form-control" value={user?.prenom || ''} readOnly />
          </div>
          <div className="form-group">
            <label>Nom</label>
            <input className="form-control" value={user?.nom || ''} readOnly />
          </div>
          <div className="form-group">
            <label>Direction</label>
            <input className="form-control" value={user?.direction?.nom || ''} readOnly />
          </div>
          <div className="form-group">
            <label>Division</label>
            <input className="form-control" value={user?.division?.nom || ''} readOnly />
          </div>
          {user?.matricule && (
            <div className="form-group">
              <label>Matricule</label>
              <input className="form-control" value={user.matricule} readOnly />
            </div>
          )}
          <div className="form-group">
            <label>Solde disponible</label>
            <input className="form-control" value={solde ? `${solde.solde_disponible} jour(s)` : '…'} readOnly />
          </div>
        </div>

        <hr className="divider" />

        {/* ── Décision active (AGENT_ETAT) ── */}
        {user?.profil === 'AGENT_ETAT' && solde?.decision_active && (
          <div className="alert alert-info" style={{ marginBottom: 16 }}>
            Décision active : du{' '}
            <strong>{new Date(solde.decision_active.date_debut).toLocaleDateString('fr-FR')}</strong>
            {' '}au{' '}
            <strong>{new Date(solde.decision_active.date_fin).toLocaleDateString('fr-FR')}</strong>
            {' '}— Réf. {solde.decision_active.numero_reference || '(en cours)'}
          </div>
        )}

        {error && <div className="alert alert-error">{error}</div>}

        {/* ── Formulaire de demande ── */}
        <form onSubmit={handleSubmit}>
          <div className="card-title" style={{ marginTop: 4 }}>Détails de la demande</div>

          {/* Type */}
          {user?.profil === 'AGENT_ETAT' && (
            <div className="form-group">
              <label>Type de demande</label>
              <select
                className="form-control"
                value={form.type}
                onChange={e => setForm(f => ({ ...f, type: e.target.value }))}
                required
              >
                <option value="">— Choisir —</option>
                <option value="DECISION">Décision (circuit 4 niveaux)</option>
                {peutSoumettreConge && (
                  <option value="CONGE">Congé pendant décision active (circuit 2 niveaux)</option>
                )}
              </select>
            </div>
          )}

          <div className="form-grid-3">
            <div className="form-group">
              <label>Date de début</label>
              <input
                type="date" className="form-control"
                min={today}
                value={form.date_debut}
                onChange={e => setForm(f => ({ ...f, date_debut: e.target.value }))}
                required
              />
            </div>
            <div className="form-group">
              <label>Date de fin</label>
              <input
                type="date" className="form-control"
                min={form.date_debut || today}
                value={form.date_fin}
                onChange={e => setForm(f => ({ ...f, date_fin: e.target.value }))}
                required
              />
            </div>
            <div className="form-group">
              <label>Nombre de jours</label>
              <input
                className="form-control"
                value={jours > 0 ? `${jours} jour(s)` : '—'}
                readOnly
                style={{ fontWeight: jours > 0 ? 700 : 400 }}
              />
            </div>
          </div>

          <div className="form-group">
            <label>Lieu de jouissance du congé</label>
            <input
              type="text" className="form-control"
              value={form.lieu_jouissance}
              onChange={e => setForm(f => ({ ...f, lieu_jouissance: e.target.value }))}
              placeholder="Ex : Dakar, Saint-Louis…"
            />
          </div>

          <div className="form-group">
            <label>Motif de la demande <span style={{ color: 'var(--danger)' }}>*</span></label>
            <textarea
              className="form-control"
              value={form.motif}
              onChange={e => setForm(f => ({ ...f, motif: e.target.value }))}
              placeholder="Indiquez le motif de votre demande…"
              required
            />
          </div>

          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <button type="button" className="btn btn-outline" onClick={() => navigate('/demandes')}>
              Annuler
            </button>
            <button type="submit" className="btn btn-primary" disabled={loading || jours <= 0}>
              {loading ? <span className="spinner" /> : 'Soumettre la demande'}
            </button>
          </div>
        </form>
      </div>
    </>
  )
}
