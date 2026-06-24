import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import StatusBadge from '../components/StatusBadge'

const ROLE_LABEL = {
  CHEF_DIVISION: 'Chef de Division',
  DIRECTEUR:     'Directeur',
  DAP:           'DAP',
  DRH:           'DRH',
  DGB:           'DGB',
  MINISTRE:      'Ministre',
}

function getCircuit(agentRole, type) {
  if (agentRole === 'DGB') return ['MINISTRE']
  if (agentRole === 'DIRECTEUR' && type === 'CONGE')    return ['DGB']
  if (agentRole === 'DIRECTEUR' && type === 'DECISION') return ['DGB', 'DRH']
  if (agentRole === 'CHEF_DIVISION' && type === 'CONGE')    return ['DIRECTEUR']
  if (agentRole === 'CHEF_DIVISION' && type === 'DECISION') return ['DIRECTEUR', 'DAP', 'DRH']
  if (type === 'DECISION') return ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH']
  return ['CHEF_DIVISION', 'DIRECTEUR']
}

function getNiveauLabel(d) {
  const circuit = getCircuit(d.agent?.role, d.type)
  const role    = circuit[d.niveau_courant - 1]
  return role
    ? `Niveau ${d.niveau_courant} : ${ROLE_LABEL[role] ?? role}`
    : `Niveau ${d.niveau_courant}`
}

function isFinalDecision(d) {
  if (d.type !== 'DECISION') return false
  return d.niveau_courant >= getCircuit(d.agent?.role, d.type).length
}

export default function ValidationsPage() {
  const navigate = useNavigate()
  const [onglet,      setOnglet]      = useState('pending')
  const [demandes,    setDemandes]    = useState([])
  const [historique,  setHistorique]  = useState([])
  const [loading,     setLoading]     = useState(true)
  const [loadingHist, setLoadingHist] = useState(false)
  const [modal,       setModal]       = useState(null)
  const [motif,       setMotif]       = useState('')
  const [dureModal,   setDureModal]   = useState(null)
  const [dureeJours,  setDureeJours]  = useState(30)
  const [sending,     setSending]     = useState(false)
  const [msgErr,      setMsgErr]      = useState('')

  const charger = () => {
    setLoading(true)
    api.get('/api/validations/pending')
      .then(r => setDemandes(r.data))
      .finally(() => setLoading(false))
  }

  const chargerHistorique = () => {
    setLoadingHist(true)
    api.get('/api/validations/history')
      .then(r => setHistorique(r.data))
      .finally(() => setLoadingHist(false))
  }

  useEffect(() => { charger() }, [])

  useEffect(() => {
    if (onglet === 'history' && historique.length === 0) chargerHistorique()
  }, [onglet])

  const valider = async (demande, avis, motifRefus = null, duree = null) => {
    setSending(true)
    setMsgErr('')
    try {
      const payload = { avis, motif_refus: motifRefus || undefined }
      if (duree !== null) payload.duree_jours = duree
      await api.post(`/api/validations/${demande.id}`, payload)
      setModal(null)
      setDureModal(null)
      setMotif('')
      charger()
      if (historique.length > 0) chargerHistorique()
    } catch (err) {
      setMsgErr(err.response?.data?.message || 'Erreur lors de la validation.')
    } finally {
      setSending(false)
    }
  }

  const handleApprouver = demande => {
    if (isFinalDecision(demande)) {
      setDureeJours(30)
      setMsgErr('')
      setDureModal(demande)
    } else {
      valider(demande, 'FAVORABLE')
    }
  }

  const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—'

  return (
    <>
      <div className="page-header">
        <div className="page-title">Validations</div>
        <div style={{ display: 'flex', gap: 8 }}>
          <button
            className={`btn btn-sm ${onglet === 'pending' ? 'btn-primary' : 'btn-outline'}`}
            onClick={() => setOnglet('pending')}
          >
            En attente{demandes.length > 0 ? ` (${demandes.length})` : ''}
          </button>
          <button
            className={`btn btn-sm ${onglet === 'history' ? 'btn-primary' : 'btn-outline'}`}
            onClick={() => setOnglet('history')}
          >
            Historique
          </button>
        </div>
      </div>

      {/* ── Onglet En attente ── */}
      {onglet === 'pending' && (
        loading ? (
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
                      <span>📁 {d.agent?.direction?.sigle ?? '—'} — {d.agent?.division?.sigle ?? '—'}</span>
                      {d.type === 'CONGE' && (
                        <span>📅 {formatDate(d.date_debut)} → {formatDate(d.date_fin)} ({d.nombre_jours}j)</span>
                      )}
                      <span>👤 {d.agent?.poste}</span>
                      <span>📋 {getNiveauLabel(d)}</span>
                      {d.agent?.matricule && <span>🪪 {d.agent.matricule}</span>}
                      {isFinalDecision(d) && (
                        <span style={{ color: 'var(--primary)', fontWeight: 600 }}>
                          ⏱ Approbation finale — durée à saisir
                        </span>
                      )}
                    </div>
                    {d.motif && (
                      <div style={{ marginTop: 8, fontSize: 12, background: 'var(--bg)',
                                    padding: '6px 10px', borderRadius: 'var(--radius)',
                                    borderLeft: '3px solid var(--border)' }}>
                        <strong>Motif :</strong> {d.motif}
                      </div>
                    )}
                  </div>

                  <div style={{ display: 'flex', flexDirection: 'column', gap: 8, marginLeft: 20 }}>
                    <button className="btn btn-outline btn-sm"
                      onClick={() => navigate(`/demandes/${d.id}`)}>
                      Voir détail
                    </button>
                    <button className="btn btn-success btn-sm"
                      disabled={sending}
                      onClick={() => handleApprouver(d)}>
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
        )
      )}

      {/* ── Onglet Historique ── */}
      {onglet === 'history' && (
        loadingHist ? (
          <div className="card"><div className="empty-state">Chargement…</div></div>
        ) : historique.length === 0 ? (
          <div className="card">
            <div className="empty-state">Aucune validation enregistrée pour le moment.</div>
          </div>
        ) : (
          <div className="card">
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Demandeur</th>
                    <th>Type</th>
                    <th>Décision</th>
                    <th>Date de traitement</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  {historique.map(v => (
                    <tr key={v.id}>
                      <td>
                        <strong>{v.demande?.agent?.prenom} {v.demande?.agent?.nom}</strong>
                        {v.demande?.agent?.matricule && (
                          <div style={{ fontSize: 11, color: 'var(--gray)' }}>
                            {v.demande.agent.matricule}
                          </div>
                        )}
                      </td>
                      <td>
                        <span style={{ fontWeight: 600, fontSize: 12,
                                       color: v.demande?.type === 'DECISION' ? 'var(--primary)' : 'var(--dgb-green)' }}>
                          {v.demande?.type ?? '—'}
                        </span>
                      </td>
                      <td>
                        <StatusBadge statut={v.avis === 'FAVORABLE' ? 'APPROUVEE' : 'REJETEE'} />
                        {v.motif_refus && (
                          <div style={{ fontSize: 11, color: 'var(--gray)', marginTop: 2 }}>
                            {v.motif_refus}
                          </div>
                        )}
                      </td>
                      <td style={{ fontSize: 12, color: 'var(--gray)' }}>
                        {formatDate(v.created_at)}
                      </td>
                      <td>
                        {v.demande?.id && (
                          <button className="btn btn-outline btn-sm"
                            onClick={() => navigate(`/demandes/${v.demande.id}`)}>
                            Détail
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )
      )}

      {/* ── Modal de refus ── */}
      {modal && (
        <div className="modal-overlay" onClick={e => { if (e.target === e.currentTarget) setModal(null) }}>
          <div className="modal-box">
            <div className="modal-title">Motif du refus</div>
            <p style={{ fontSize: 13, color: 'var(--gray)', marginBottom: 12 }}>
              Demande de <strong>{modal.agent?.prenom} {modal.agent?.nom}</strong> —{' '}
              {modal.type === 'CONGE' ? `${modal.nombre_jours} jour(s)` : 'DÉCISION'}
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

      {/* ── Modal durée de validité (approbation finale DECISION — DRH uniquement) ── */}
      {dureModal && (
        <div className="modal-overlay" onClick={e => { if (e.target === e.currentTarget) setDureModal(null) }}>
          <div className="modal-box">
            <div className="modal-title">Durée de validité de la décision</div>
            <p style={{ fontSize: 13, color: 'var(--gray)', marginBottom: 12 }}>
              Approbation finale pour{' '}
              <strong>{dureModal.agent?.prenom} {dureModal.agent?.nom}</strong>.
              Indiquez la durée pendant laquelle le fonctionnaire pourra soumettre des congés.
            </p>
            {msgErr && <div className="alert alert-error">{msgErr}</div>}
            <div className="form-group">
              <label>Durée de validité (1 à 90 jours) <span style={{ color: 'var(--danger)' }}>*</span></label>
              <input
                type="number"
                className="form-control"
                min={1}
                max={90}
                value={dureeJours}
                onChange={e => setDureeJours(Math.min(90, Math.max(1, parseInt(e.target.value) || 1)))}
                autoFocus
              />
              <p style={{ fontSize: 11, color: 'var(--gray)', marginTop: 4 }}>
                La décision sera valide pendant {dureeJours} jour(s) à compter de la date d'approbation.
              </p>
            </div>
            <div className="modal-actions">
              <button className="btn btn-outline" onClick={() => setDureModal(null)}>Annuler</button>
              <button
                className="btn btn-success"
                disabled={sending || !dureeJours || dureeJours < 1 || dureeJours > 90}
                onClick={() => valider(dureModal, 'FAVORABLE', null, dureeJours)}
              >
                {sending ? <span className="spinner" /> : `✅ Approuver (${dureeJours} jour(s))`}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
