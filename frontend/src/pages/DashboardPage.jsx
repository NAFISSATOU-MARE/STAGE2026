import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'
import StatusBadge from '../components/StatusBadge'

const VALIDATOR_ROLES = ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE']

export default function DashboardPage() {
  const { user }     = useAuth()
  const navigate     = useNavigate()
  const [solde,      setSolde]      = useState(null)
  const [demandes,   setDemandes]   = useState([])
  const [pending,    setPending]    = useState(0)

  useEffect(() => {
    api.get('/api/solde').then(r => setSolde(r.data))
    api.get('/api/demandes').then(r => setDemandes(r.data.slice(0, 5)))
    if (VALIDATOR_ROLES.includes(user?.role)) {
      api.get('/api/validations/pending').then(r => setPending(r.data.length))
    }
  }, [user])

  const formatDate = d => d ? new Date(d).toLocaleDateString('fr-FR') : '—'

  return (
    <>
      {/* ── Bienvenue ── */}
      <div className="page-header">
        <div>
          <div className="page-title">Bonjour, {user?.prenom} {user?.nom}</div>
          <div style={{ color: 'var(--gray)', fontSize: 13, marginTop: 4 }}>
            {user?.direction?.nom} — {user?.division?.nom}
          </div>
        </div>
        {user?.profil === 'AGENT_ETAT' ? (
          <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap' }}>
            <button
              className="btn btn-primary"
              onClick={() => navigate('/demandes/new?type=DECISION')}
              disabled={solde !== null && !!solde.decision_active}
              title={solde?.decision_active
                ? 'Vous avez déjà une décision active en cours'
                : undefined}
            >
              ✏️ Demande de décision
            </button>
            <button
              className="btn btn-primary"
              onClick={() => navigate('/demandes/new?type=CONGE')}
              disabled={solde !== null && !solde.peut_soumettre_conge}
              title={solde && !solde.peut_soumettre_conge
                ? 'Aucune décision active — vous ne pouvez pas encore déposer un congé'
                : undefined}
            >
              ✏️ Demande de congé
            </button>
          </div>
        ) : (
          <button className="btn btn-primary" onClick={() => navigate('/demandes/new')}>
            {user?.profil === 'CONTRACTUEL' ? '✏️ Demande de congé' : '✏️ Nouvelle demande'}
          </button>
        )}
      </div>

      {/* ── Cartes statistiques ── */}
      <div className="cards-row">
        <div className="stat-card">
          <span className="stat-icon">📅</span>
          <div className="value">{solde?.solde_disponible ?? '—'}</div>
          <div className="label">
            {user?.profil === 'AGENT_ETAT' && !solde?.decision_active
              ? 'Jours à pouvoir'
              : 'Jours disponibles'}
          </div>
        </div>

        <div className="stat-card orange">
          <span className="stat-icon">⏳</span>
          <div className="value">{demandes.filter(d => d.statut === 'EN_ATTENTE').length}</div>
          <div className="label">En attente</div>
        </div>

        <div className="stat-card green">
          <span className="stat-icon">✅</span>
          <div className="value">{demandes.filter(d => d.statut === 'APPROUVEE').length}</div>
          <div className="label">Approuvées</div>
        </div>

        {VALIDATOR_ROLES.includes(user?.role) && (
          <div className="stat-card purple" style={{ cursor: 'pointer' }}
            onClick={() => navigate('/validations')}>
            <span className="stat-icon">🔔</span>
            <div className="value">{pending}</div>
            <div className="label">À valider</div>
          </div>
        )}
      </div>

      {/* ── Profil & décision active ── */}
      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 16, marginBottom: 24 }}>
        <div className="card">
          <div className="card-title">Mon profil</div>
          <div className="info-row"><span className="info-label">Profil :</span>
            <span>{user?.profil === 'AGENT_ETAT' ? 'Fonctionnaire' : 'Contractuel'}</span></div>
          <div className="info-row"><span className="info-label">Téléphone :</span>
            <span>{user?.telephone || '—'}</span></div>
          <div className="info-row"><span className="info-label">Matricule :</span>
            <span>{user?.matricule || '—'}</span></div>
          <div className="info-row"><span className="info-label">Corps :</span>
            <span>{user?.corps || '—'}</span></div>
          <div className="info-row"><span className="info-label">Poste :</span>
            <span>{user?.poste}</span></div>
          <div className="info-row"><span className="info-label">Rôle :</span>
            <span>{user?.role}</span></div>
        </div>

        <div className="card">
          <div className="card-title">Situation congés</div>
          <div className="info-row"><span className="info-label">Solde disponible :</span>
            <strong>{solde?.solde_disponible ?? '—'} jour(s)</strong></div>
          {user?.profil === 'AGENT_ETAT' && (
            <div className="info-row"><span className="info-label">Décision active :</span>
              <span>
                {solde?.decision_active
                  ? `Valide jusqu'au ${formatDate(solde.decision_active.date_expiration)}`
                  : 'Aucune (ou expirée)'}
              </span>
            </div>
          )}
          <div className="info-row"><span className="info-label">Peut déposer un congé :</span>
            <span>{solde?.peut_soumettre_conge ? '✅ Oui' : '❌ Non'}</span></div>
        </div>
      </div>

      {/* ── Dernières demandes ── */}
      <div className="card">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14 }}>
          <div className="card-title" style={{ marginBottom: 0 }}>Mes dernières demandes</div>
          <button className="btn btn-outline btn-sm" onClick={() => navigate('/demandes')}>
            Voir tout
          </button>
        </div>
        {demandes.length === 0 ? (
          <div className="empty-state">Aucune demande pour le moment.</div>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Type</th><th>Date début</th><th>Date fin</th>
                  <th>Jours</th><th>Statut</th><th></th>
                </tr>
              </thead>
              <tbody>
                {demandes.map(d => (
                  <tr key={d.id}>
                    <td>{d.type}</td>
                    <td>{formatDate(d.date_debut)}</td>
                    <td>{formatDate(d.date_fin)}</td>
                    <td>{d.nombre_jours}</td>
                    <td><StatusBadge statut={d.statut} /></td>
                    <td>
                      <button className="btn btn-outline btn-sm" onClick={() => navigate(`/demandes/${d.id}`)}>
                        Détail
                      </button>
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
