import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'

const NIVEAU_LABEL = {
  1: 'Chef de Division', 2: 'Directeur', 3: 'DAP', 4: 'DRH'
}

export default function ValidationsPage() {
  const navigate = useNavigate()
  const [demandes, setDemandes]   = useState([])
  const [loading,  setLoading]    = useState(true)
  const [modal,    setModal]      = useState(null)   // { demande, action:'DEFAVORABLE' }
  const [motif,    setMotif]      = useState('')
  const [sending,  setSending]    = useState(false)
  const [msgErr,   setMsgErr]     = useState('')

  const charger = () => {
    setLoading(true)
    api.get('/api/validations/pending').then(r => setDemandes(r.data)).finally(() => setLoading(false))
  }

  useEffect(() => { charger() }, [])

  const valider = async (demande, avis, motifRefus = null) => {
    setSending(true)
    setMsgErr('')
    try {
      await api.post(`/api/validations/${demande.id}`, {
        avis,
        motif_refus: motifRefus || undefined,
      })
      setModal(null)
      setMotif('')
      charger()
    } catch (err) {
      setMsgErr(err.response?.data?.message || 'Erreur lors de la validation.')
    } finally {
      setSending(false)
    }
  }

  const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—'

  return (
    <>
      <div className="page-header">
        <div className="page-title">Demandes à valider</div>
        <span style={{ fontSize: 13, color: 'var(--gray)' }}>
          {demandes.length} demande(s) en attente
        </span>
      </div>

      {loading ? (
        <div className="card"><div className="empty-state">Chargement…</div></div>
      ) : demandes.length === 0 ? (
        <div className="card">
          <div className="empty-state">✅ Aucune demande en attente de validation.</div>
        </div>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {demandes.map(d => (
            <div key={d.id} className="card">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>

                {/* Infos agent + demande */}
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 700, fontSize: 15, marginBottom: 6 }}>
                    {d.agent?.prenom} {d.agent?.nom}
                    <span style={{ marginLeft: 10, fontSize: 11, background: 'var(--primary-bg)',
                                   color: 'var(--primary)', padding: '2px 8px', borderRadius: 10,
                                   fontWeight: 600 }}>
                      {d.type}
                    </span>
                  </div>
                  <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '4px 24px',
                                fontSize: 12, color: 'var(--gray)' }}>
                    <span>📁 {d.agent?.direction?.sigle} — {d.agent?.division?.sigle}</span>
                    <span>📅 {formatDate(d.date_debut)} → {formatDate(d.date_fin)} ({d.nombre_jours}j)</span>
                    <span>👤 {d.agent?.poste}</span>
                    <span>📋 Niveau {d.niveau_courant} : {NIVEAU_LABEL[d.niveau_courant]}</span>
                    {d.agent?.matricule && <span>🪪 {d.agent.matricule}</span>}
                  </div>
                  {d.motif && (
                    <div style={{ marginTop: 8, fontSize: 12, background: 'var(--bg)',
                                  padding: '6px 10px', borderRadius: 'var(--radius)', borderLeft: '3px solid var(--border)' }}>
                      <strong>Motif :</strong> {d.motif}
                    </div>
                  )}
                </div>

                {/* Boutons */}
                <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginLeft: 20 }}>
                  <button className="btn btn-outline btn-sm"
                    onClick={() => navigate(`/demandes/${d.id}`)}>
                    Voir détail
                  </button>
                  <button className="btn btn-success btn-sm"
                    disabled={sending}
                    onClick={() => valider(d, 'FAVORABLE')}>
                    ✅ Approuver
                  </button>
                  <button className="btn btn-danger btn-sm"
                    disabled={sending}
                    onClick={() => { setModal(d); setMotif(''); setMsgErr('') }}>
                    ❌ Rejeter
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* ── Modal de refus ── */}
      {modal && (
        <div className="modal-overlay" onClick={e => { if (e.target === e.currentTarget) setModal(null) }}>
          <div className="modal-box">
            <div className="modal-title">Motif du refus</div>
            <p style={{ fontSize: 13, color: 'var(--gray)', marginBottom: 12 }}>
              Demande de <strong>{modal.agent?.prenom} {modal.agent?.nom}</strong> —
              {modal.nombre_jours} jour(s) ({modal.type})
            </p>
            {msgErr && <div className="alert alert-error">{msgErr}</div>}
            <div className="form-group">
              <label>Motif obligatoire</label>
              <textarea
                className="form-control"
                value={motif}
                onChange={e => setMotif(e.target.value)}
                placeholder="Expliquez la raison du refus…"
                autoFocus
              />
            </div>
            <div className="modal-actions">
              <button className="btn btn-outline" onClick={() => setModal(null)}>Annuler</button>
              <button
                className="btn btn-danger"
                disabled={sending || !motif.trim()}
                onClick={() => valider(modal, 'DEFAVORABLE', motif.trim())}
              >
                {sending ? <span className="spinner" /> : 'Confirmer le refus'}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
