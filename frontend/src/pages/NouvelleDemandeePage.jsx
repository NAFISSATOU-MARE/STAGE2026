import { useState, useEffect } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'

export default function NouvelleDemandeePage() {
  const { user, refreshUser } = useAuth()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const urlType = searchParams.get('type') // 'DECISION' | 'CONGE' | null

  const [solde,   setSolde]   = useState(null)
  const [form,    setForm]    = useState({
    type: '', date_debut: '', date_fin: '', motif: '', lieu_jouissance: ''
  })
  const [jours,   setJours]   = useState(0)
  const [error,   setError]   = useState('')
  const [loading, setLoading] = useState(false)

  const ROLES_CHOIX_LIBRE = ['CHEF_DIVISION', 'DIRECTEUR', 'DGB']

  useEffect(() => {
    api.get('/api/solde').then(r => {
      setSolde(r.data)
      if (urlType) {
        setForm(f => ({
          ...f,
          type:  urlType,
          motif: urlType === 'DECISION' ? 'Demande de décision' : f.motif,
        }))
      } else if (user?.profil === 'CONTRACTUEL') {
        setForm(f => ({ ...f, type: 'CONGE' }))
      } else if (ROLES_CHOIX_LIBRE.includes(user?.role)) {
        // laisser vide pour le sélecteur
      } else if (user?.profil === 'AGENT_ETAT') {
        const type = r.data.decision_active ? 'CONGE' : 'DECISION'
        setForm(f => ({
          ...f,
          type,
          motif: type === 'DECISION' ? 'Demande de décision' : f.motif,
        }))
      }
    })
  }, [user, urlType])

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
    const isDecisionSubmit = form.type === 'DECISION'
    if (!isDecisionSubmit && jours <= 0) {
      setError('La date de fin doit être après la date de début.')
      return
    }
    setLoading(true)
    try {
      const payload = { type: form.type, motif: form.motif }
      if (!isDecisionSubmit) {
        payload.date_debut      = form.date_debut
        payload.date_fin        = form.date_fin
        if (form.lieu_jouissance) payload.lieu_jouissance = form.lieu_jouissance
      }
      const r = await api.post('/api/demandes', payload)
      await refreshUser()
      navigate(`/demandes/${r.data.id}`)
    } catch (err) {
      setError(err.response?.data?.message || 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  const decisionActive = solde?.decision_active ?? null
  const isDecision = form.type === 'DECISION'
  const isConge    = form.type === 'CONGE'
  const today = new Date().toISOString().split('T')[0]

  const pageTitle = isDecision
    ? 'Demande de décision'
    : isConge
      ? 'Demande de congé'
      : 'Nouvelle demande'

  return (
    <>
      <div className="page-header">
        <div className="page-title">{pageTitle}</div>
      </div>

      <div className="card" style={{ maxWidth: 720 }}>

        {/* ── Identité (lecture seule) ── */}
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
          {user?.matricule && (
            <div className="form-group">
              <label>Matricule</label>
              <input className="form-control" value={user.matricule} readOnly />
            </div>
          )}
          {user?.corps && (
            <div className="form-group">
              <label>Corps</label>
              <input className="form-control" value={user.corps} readOnly />
            </div>
          )}
          {user?.poste && (
            <div className="form-group">
              <label>Poste</label>
              <input className="form-control" value={user.poste} readOnly />
            </div>
          )}
          <div className="form-group">
            <label>Direction</label>
            <input className="form-control" value={user?.direction?.nom || ''} readOnly />
          </div>
          <div className="form-group">
            <label>Division</label>
            <input className="form-control" value={user?.division?.nom || ''} readOnly />
          </div>
          {isConge && (
            <div className="form-group">
              <label>Solde disponible</label>
              <input className="form-control" value={solde ? `${solde.solde_disponible} jour(s)` : '…'} readOnly />
            </div>
          )}
        </div>

        <hr className="divider" />

        {/* ── Bannière décision active (congé seulement) ── */}
        {isConge && user?.profil === 'AGENT_ETAT' && decisionActive && (
          <div className="alert alert-info" style={{ marginBottom: 16 }}>
            Décision active — validée le{' '}
            <strong>{new Date(decisionActive.date_validation).toLocaleDateString('fr-FR')}</strong>
            , valable jusqu'au{' '}
            <strong>{new Date(decisionActive.date_expiration).toLocaleDateString('fr-FR')}</strong>
            {decisionActive.numero_reference && (
              <> — Réf.&nbsp;{decisionActive.numero_reference}</>
            )}
          </div>
        )}

        {error && <div className="alert alert-error">{error}</div>}

        <form onSubmit={handleSubmit}>
          <div className="card-title" style={{ marginTop: 4 }}>Détails de la demande</div>

          {/* Type */}
          {ROLES_CHOIX_LIBRE.includes(user?.role) && !urlType ? (
            <div className="form-group">
              <label>Type de demande <span style={{ color: 'var(--danger)' }}>*</span></label>
              <select
                className="form-control"
                value={form.type}
                onChange={e => {
                  const t = e.target.value
                  setForm(f => ({
                    ...f,
                    type:  t,
                    motif: t === 'DECISION' ? 'Demande de décision' : (f.motif === 'Demande de décision' ? '' : f.motif),
                  }))
                }}
                required
                style={{ fontWeight: 600 }}
              >
                <option value="">— Sélectionner —</option>
                <option value="CONGE">CONGÉ</option>
                <option value="DECISION">DÉCISION</option>
              </select>
            </div>
          ) : form.type ? (
            <div className="form-group">
              <label>Type de demande</label>
              <input
                className="form-control"
                value={isDecision ? 'DÉCISION — affectation / nomination' : 'CONGÉ'}
                readOnly
                style={{ fontWeight: 600, color: 'var(--primary)', cursor: 'default' }}
              />
            </div>
          ) : null}

          {/* Dates — uniquement pour les congés */}
          {isConge && (
            <div className="form-grid-3">
              <div className="form-group">
                <label>Date de début <span style={{ color: 'var(--danger)' }}>*</span></label>
                <input
                  type="date" className="form-control"
                  min={today}
                  value={form.date_debut}
                  onChange={e => setForm(f => ({ ...f, date_debut: e.target.value }))}
                  required
                />
              </div>
              <div className="form-group">
                <label>Date de fin <span style={{ color: 'var(--danger)' }}>*</span></label>
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
          )}

          {/* Lieu de jouissance — uniquement pour les congés */}
          {isConge && (
            <div className="form-group">
              <label>Lieu de jouissance du congé</label>
              <input
                type="text" className="form-control"
                value={form.lieu_jouissance}
                onChange={e => setForm(f => ({ ...f, lieu_jouissance: e.target.value }))}
                placeholder="Ex : Dakar, Saint-Louis…"
              />
            </div>
          )}

          {/* Motif */}
          <div className="form-group">
            <label>Motif de la demande <span style={{ color: 'var(--danger)' }}>*</span></label>
            {isDecision ? (
              <input
                className="form-control"
                value={form.motif}
                readOnly
                style={{ cursor: 'default', color: 'var(--gray)' }}
              />
            ) : (
              <textarea
                className="form-control"
                value={form.motif}
                onChange={e => setForm(f => ({ ...f, motif: e.target.value }))}
                placeholder="Indiquez le motif de votre demande…"
                required
              />
            )}
          </div>

          <div style={{ display: 'flex', gap: 12, justifyContent: 'flex-end' }}>
            <button type="button" className="btn btn-outline" onClick={() => navigate(-1)}>
              Annuler
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={loading || (!isDecision && jours <= 0)}
            >
              {loading ? <span className="spinner" /> : 'Soumettre la demande'}
            </button>
          </div>
        </form>
      </div>
    </>
  )
}
