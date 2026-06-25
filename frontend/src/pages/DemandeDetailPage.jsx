import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import api from '../api/axios'
import StatusBadge from '../components/StatusBadge'
import { useAuth } from '../context/AuthContext'

const VALIDATOR_ROLES = ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE']

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
  if (agentRole === 'DRH') return ['DGB']
  if (agentRole === 'DIRECTEUR' && type === 'CONGE')    return ['DGB']
  if (agentRole === 'DIRECTEUR' && type === 'DECISION') return ['DGB', 'DRH']
  if (agentRole === 'CHEF_DIVISION' && type === 'CONGE')    return ['DIRECTEUR']
  if (agentRole === 'CHEF_DIVISION' && type === 'DECISION') return ['DIRECTEUR', 'DAP', 'DRH']
  if (type === 'DECISION') return ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH']
  return ['CHEF_DIVISION', 'DIRECTEUR']
}

function getNiveauLabel(demande, niveau) {
  const circuit = getCircuit(demande.agent?.role, demande.type)
  const role    = circuit[niveau - 1]
  return role
    ? `Niveau ${niveau} — ${ROLE_LABEL[role] ?? role}`
    : `Niveau ${niveau}`
}

export default function DemandeDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { user } = useAuth()
  const isValidator = VALIDATOR_ROLES.includes(user?.role)

  const [demande,    setDemande]    = useState(null)
  const [loading,    setLoading]    = useState(true)
  const [pdfLoad,    setPdfLoad]    = useState(false)
  const [pdfBlobUrl, setPdfBlobUrl] = useState(null)

  useEffect(() => {
    api.get(`/api/demandes/${id}`)
      .then(r => setDemande(r.data))
      .finally(() => setLoading(false))
  }, [id])

  // Chargement auto du PDF uniquement pour les CONGÉS vus par un validateur
  useEffect(() => {
    if (!demande || !isValidator || demande.type !== 'CONGE') return
    let url
    api.get(`/api/demandes/${id}/pdf`, { responseType: 'blob' })
      .then(r => {
        url = URL.createObjectURL(new Blob([r.data], { type: 'application/pdf' }))
        setPdfBlobUrl(url)
      })
      .catch(() => {})
    return () => { if (url) URL.revokeObjectURL(url) }
  }, [demande?.id, demande?.type, isValidator, id])

  const telechargerPdf = async () => {
    setPdfLoad(true)
    try {
      const r    = await api.get(`/api/demandes/${id}/pdf`, { responseType: 'blob' })
      const url  = URL.createObjectURL(new Blob([r.data], { type: 'application/pdf' }))
      const link = document.createElement('a')
      const ref  = demande.numero_reference?.replace(/\//g, '-') || `DGB-${id}`
      link.href  = url
      link.download = `demande-${ref}.pdf`
      link.click()
      URL.revokeObjectURL(url)
    } catch {
      alert('Erreur lors de la génération du PDF.')
    } finally {
      setPdfLoad(false)
    }
  }

  const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—'

  if (loading) return <div className="card"><div className="empty-state">Chargement…</div></div>
  if (!demande) return <div className="card"><div className="empty-state">Demande introuvable.</div></div>

  const { agent } = demande
  const isConge    = demande.type === 'CONGE'
  const isDecision = demande.type === 'DECISION'

  return (
    <>
      <div className="page-header">
        <div>
          <button className="btn btn-outline btn-sm" onClick={() => navigate(-1)} style={{ marginBottom: 8 }}>
            ← Retour
          </button>
          <div className="page-title">
            Demande #{demande.id}
            {demande.numero_reference && (
              <span style={{ fontSize: 13, fontWeight: 400, color: 'var(--gray)', marginLeft: 10 }}>
                Réf. {demande.numero_reference}
              </span>
            )}
          </div>
        </div>
        {/* Bouton PDF uniquement pour les congés */}
        {isConge && (
          <button className="btn btn-primary" onClick={telechargerPdf} disabled={pdfLoad}>
            {pdfLoad ? <span className="spinner" /> : '📄 Télécharger PDF'}
          </button>
        )}
      </div>

      {/* ── Vue validateur — CONGÉ : iframe PDF ── */}
      {isValidator && isConge && (
        <div className="card" style={{ padding: 0, overflow: 'hidden', minHeight: '80vh' }}>
          {pdfBlobUrl
            ? <iframe
                src={pdfBlobUrl}
                title="Document de la demande"
                style={{ width: '100%', height: '80vh', border: 'none', display: 'block' }}
              />
            : <div className="empty-state">Chargement du document…</div>
          }
        </div>
      )}

      {/* ── Vue structurée — DÉCISION (validateurs et demandeurs) ou CONGÉ vu par le demandeur ── */}
      {(!isValidator || isDecision) && (
        <>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 16 }}>
            {/* Infos agent */}
            <div className="card">
              <div className="card-title">Agent</div>
              <div className="info-row"><span className="info-label">Nom complet :</span>{agent?.prenom} {agent?.nom}</div>
              <div className="info-row"><span className="info-label">Matricule :</span>{agent?.matricule || '—'}</div>
              <div className="info-row"><span className="info-label">Corps :</span>{agent?.corps || '—'}</div>
              <div className="info-row"><span className="info-label">Poste :</span>{agent?.poste}</div>
              <div className="info-row"><span className="info-label">Profil :</span>
                {agent?.profil === 'AGENT_ETAT' ? 'Fonctionnaire' : 'Contractuel'}</div>
              <div className="info-row"><span className="info-label">Direction :</span>{agent?.direction?.nom}</div>
              <div className="info-row"><span className="info-label">Division :</span>{agent?.division?.nom}</div>
            </div>

            {/* Infos demande */}
            <div className="card">
              <div className="card-title">Détails de la demande</div>
              <div className="info-row">
                <span className="info-label">Type :</span>
                <span style={{ fontWeight: 600 }}>{demande.type}</span>
              </div>
              {isConge && (
                <>
                  <div className="info-row"><span className="info-label">Date de début :</span>{formatDate(demande.date_debut)}</div>
                  <div className="info-row"><span className="info-label">Date de fin :</span>{formatDate(demande.date_fin)}</div>
                  <div className="info-row">
                    <span className="info-label">Nombre de jours :</span>
                    <strong>{demande.nombre_jours} jour(s)</strong>
                  </div>
                  <div className="info-row"><span className="info-label">Lieu de jouissance :</span>{demande.lieu_jouissance || '—'}</div>
                </>
              )}
              <div className="info-row">
                <span className="info-label">Statut :</span>
                <StatusBadge statut={demande.statut} />
              </div>
              {demande.statut === 'EN_ATTENTE' && (
                <div className="info-row">
                  <span className="info-label">Niveau en cours :</span>
                  {getNiveauLabel(demande, demande.niveau_courant)}
                </div>
              )}
            </div>
          </div>

          {/* Motif */}
          {demande.motif && (
            <div className="card" style={{ marginBottom: 16 }}>
              <div className="card-title">Motif de la demande</div>
              <p style={{ fontSize: 13, lineHeight: 1.6 }}>{demande.motif}</p>
            </div>
          )}

          {/* Historique des validations */}
          <div className="card">
            <div className="card-title">Historique des avis</div>
            {demande.validations?.length === 0 ? (
              <div className="empty-state">Aucun avis enregistré.</div>
            ) : (
              <div className="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Niveau</th><th>Valideur</th><th>Rôle</th>
                      <th>Avis</th><th>Motif de refus</th><th>Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    {demande.validations?.map(v => (
                      <tr key={v.id}>
                        <td>{getNiveauLabel(demande, v.niveau)}</td>
                        <td>{v.valideur?.prenom} {v.valideur?.nom}</td>
                        <td style={{ fontSize: 11 }}>{v.valideur?.role}</td>
                        <td>
                          <span style={{
                            fontWeight: 700,
                            color: v.avis === 'FAVORABLE' ? 'var(--success)' : 'var(--danger)',
                          }}>
                            {v.avis === 'FAVORABLE' ? '✅ Favorable' : '❌ Défavorable'}
                          </span>
                        </td>
                        <td style={{ fontSize: 12, fontStyle: v.motif_refus ? 'normal' : 'italic',
                                     color: v.motif_refus ? '#222' : 'var(--gray)' }}>
                          {v.motif_refus || '—'}
                        </td>
                        <td style={{ fontSize: 12 }}>
                          {new Date(v.created_at).toLocaleDateString('fr-FR')}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        </>
      )}
    </>
  )
}
