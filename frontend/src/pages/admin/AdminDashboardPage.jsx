import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../../api/axios'
import StatusBadge from '../../components/StatusBadge'

export default function AdminDashboardPage() {
  const navigate = useNavigate()
  const [stats,   setStats]   = useState(null)
  const [loading, setLoading] = useState(true)
  const [filtre,  setFiltre]  = useState({ statut: '', type: '' })

  useEffect(() => {
    api.get('/api/admin/dashboard')
      .then(r => setStats(r.data))
      .finally(() => setLoading(false))
  }, [])

  const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—'

  const recentes = (stats?.recentes ?? []).filter(d => {
    const okS = !filtre.statut || d.statut === filtre.statut
    const okT = !filtre.type   || d.type   === filtre.type
    return okS && okT
  })

  if (loading) return (
    <div className="card"><div className="empty-state">Chargement du tableau de bord…</div></div>
  )

  return (
    <>
      <div className="page-header">
        <div className="page-title">Tableau de bord — Administration</div>
        <button className="btn btn-outline" onClick={() => navigate('/admin/agents')}>
          👥 Gérer les agents
        </button>
      </div>

      {/* ── Stat cards ── */}
      <div className="cards-row">
        <div className="stat-card">
          <span className="stat-icon">👤</span>
          <div className="value">{stats.total_agents}</div>
          <div className="label">Agents</div>
        </div>
        <div className="stat-card sky">
          <span className="stat-icon">📋</span>
          <div className="value">{stats.total_demandes}</div>
          <div className="label">Demandes totales</div>
        </div>
        <div className="stat-card orange">
          <span className="stat-icon">⏳</span>
          <div className="value">{stats.en_attente}</div>
          <div className="label">En attente</div>
        </div>
        <div className="stat-card green">
          <span className="stat-icon">✅</span>
          <div className="value">{stats.approuvees}</div>
          <div className="label">Approuvées</div>
        </div>
        <div className="stat-card red">
          <span className="stat-icon">❌</span>
          <div className="value">{stats.rejetees}</div>
          <div className="label">Rejetées</div>
        </div>
        <div className="stat-card teal">
          <span className="stat-icon">📝</span>
          <div className="value">{stats.decisions}</div>
          <div className="label">Décisions</div>
        </div>
        <div className="stat-card green">
          <span className="stat-icon">🏖️</span>
          <div className="value">{stats.conges}</div>
          <div className="label">Congés</div>
        </div>
      </div>

      {/* ── Demandes récentes ── */}
      <div className="card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 }}>
          <div className="card-title" style={{ margin: 0 }}>Demandes récentes</div>
          <div style={{ display: 'flex', gap: 10 }}>
            <select className="form-control" style={{ width: 140 }}
              value={filtre.statut} onChange={e => setFiltre(f => ({ ...f, statut: e.target.value }))}>
              <option value="">Tous statuts</option>
              <option value="EN_ATTENTE">En attente</option>
              <option value="APPROUVEE">Approuvée</option>
              <option value="REJETEE">Rejetée</option>
            </select>
            <select className="form-control" style={{ width: 130 }}
              value={filtre.type} onChange={e => setFiltre(f => ({ ...f, type: e.target.value }))}>
              <option value="">Tous types</option>
              <option value="CONGE">Congé</option>
              <option value="DECISION">Décision</option>
            </select>
          </div>
        </div>

        {recentes.length === 0 ? (
          <div className="empty-state">Aucune demande.</div>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Agent</th><th>Direction</th><th>Type</th>
                  <th>Dates</th><th>Jours</th><th>Statut</th><th>Niveau</th>
                </tr>
              </thead>
              <tbody>
                {recentes.map(d => (
                  <tr key={d.id} style={{ cursor: 'pointer' }}
                    onClick={() => navigate(`/demandes/${d.id}`)}>
                    <td>
                      <div style={{ fontWeight: 600, fontSize: 13 }}>
                        {d.agent?.prenom} {d.agent?.nom}
                      </div>
                      <div style={{ fontSize: 11, color: 'var(--gray)' }}>{d.agent?.poste}</div>
                    </td>
                    <td style={{ fontSize: 12 }}>
                      {d.agent?.direction?.sigle ?? '—'}
                      {d.agent?.division && (
                        <div style={{ color: 'var(--gray)', fontSize: 11 }}>{d.agent.division.sigle}</div>
                      )}
                    </td>
                    <td>
                      <span style={{
                        fontSize: 11, padding: '2px 8px', borderRadius: 10, fontWeight: 600,
                        background: d.type === 'DECISION' ? 'var(--primary-bg)' : 'var(--dgb-green-bg)',
                        color:      d.type === 'DECISION' ? 'var(--primary)'    : 'var(--dgb-green)',
                      }}>
                        {d.type}
                      </span>
                    </td>
                    <td style={{ fontSize: 12 }}>
                      {formatDate(d.date_debut)}<br />
                      <span style={{ color: 'var(--gray)' }}>{formatDate(d.date_fin)}</span>
                    </td>
                    <td style={{ fontWeight: 600 }}>{d.nombre_jours}j</td>
                    <td><StatusBadge statut={d.statut} /></td>
                    <td style={{ fontSize: 12, color: 'var(--gray)' }}>
                      {d.statut === 'EN_ATTENTE' ? `Niv. ${d.niveau_courant}` : '—'}
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
