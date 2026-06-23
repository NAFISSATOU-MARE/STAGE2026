import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import StatusBadge from '../components/StatusBadge'

export default function MesDemandesPage() {
  const navigate = useNavigate()
  const [demandes, setDemandes] = useState([])
  const [annee,    setAnnee]    = useState('')
  const [loading,  setLoading]  = useState(true)

  const charger = (a = '') => {
    setLoading(true)
    const url = a ? `/api/demandes?annee=${a}` : '/api/demandes'
    api.get(url).then(r => setDemandes(r.data)).finally(() => setLoading(false))
  }

  useEffect(() => { charger() }, [])

  const telechargerPdf = async (demande) => {
    try {
      const r = await api.get(`/api/demandes/${demande.id}/pdf`, { responseType: 'blob' })
      const url  = URL.createObjectURL(new Blob([r.data], { type: 'application/pdf' }))
      const link = document.createElement('a')
      const ref  = demande.numero_reference?.replace(/\//g, '-') || `DGB-${demande.id}`
      link.href  = url
      link.download = `demande-${ref}.pdf`
      link.click()
      URL.revokeObjectURL(url)
    } catch {
      alert('Erreur lors de la génération du PDF.')
    }
  }

  const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—'
  const anneesCourantes = [...new Set(demandes.map(d => d.annee))].sort((a, b) => b - a)

  return (
    <>
      <div className="page-header">
        <div className="page-title">Mes demandes</div>
        <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
          <select
            className="form-control"
            style={{ width: 120 }}
            value={annee}
            onChange={e => { setAnnee(e.target.value); charger(e.target.value) }}
          >
            <option value="">Toutes les années</option>
            {anneesCourantes.map(a => <option key={a} value={a}>{a}</option>)}
          </select>
          <button className="btn btn-primary" onClick={() => navigate('/demandes/new')}>
            + Nouvelle
          </button>
        </div>
      </div>

      <div className="card">
        {loading ? (
          <div className="empty-state">Chargement…</div>
        ) : demandes.length === 0 ? (
          <div className="empty-state">Aucune demande trouvée.</div>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Réf.</th><th>Type</th><th>Date début</th><th>Date fin</th>
                  <th>Jours</th><th>Statut</th><th>Niveau</th><th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {demandes.map(d => (
                  <tr key={d.id}>
                    <td style={{ fontFamily: 'monospace', fontSize: 11 }}>
                      {d.numero_reference || `#${d.id}`}
                    </td>
                    <td>
                      <span style={{ fontSize: 11, background: d.type === 'DECISION' ? 'var(--primary-bg)' : 'var(--success-bg)',
                                     color: d.type === 'DECISION' ? 'var(--primary)' : 'var(--success)',
                                     padding: '2px 8px', borderRadius: 10, fontWeight: 600 }}>
                        {d.type}
                      </span>
                    </td>
                    <td>{formatDate(d.date_debut)}</td>
                    <td>{formatDate(d.date_fin)}</td>
                    <td>{d.nombre_jours}</td>
                    <td><StatusBadge statut={d.statut} /></td>
                    <td style={{ fontSize: 12, color: 'var(--gray)' }}>
                      {d.statut === 'EN_ATTENTE' ? `Niveau ${d.niveau_courant}` : '—'}
                    </td>
                    <td>
                      <div style={{ display: 'flex', gap: 6 }}>
                        <button className="btn btn-outline btn-sm"
                          onClick={() => navigate(`/demandes/${d.id}`)}>
                          Détail
                        </button>
                        {d.statut === 'APPROUVEE' && (
                          <button className="btn btn-primary btn-sm"
                            onClick={() => telechargerPdf(d)}>
                            PDF
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </>
  )
}
