import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'
import StatusBadge from '../components/StatusBadge'
import { useAuth } from '../context/AuthContext'

export default function MesDemandesPage() {
  const navigate  = useNavigate()
  const { user }  = useAuth()
  const [demandes, setDemandes] = useState([])
  const [annee,    setAnnee]    = useState('')
  const [loading,  setLoading]  = useState(true)

  // ── Modal édition lettre ──────────────────────────────────────────────────
  const [modalDemande,   setModalDemande]   = useState(null)
  const [lettreForm,     setLettreForm]     = useState({ motif_lettre: '', lieu_jouissance: '', complement: '' })
  const [lettreLoading,  setLettreLoading]  = useState(false)
  const [lettreError,    setLettreError]    = useState('')

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
      link.href     = url
      link.download = `demande-${ref}.pdf`
      link.click()
      URL.revokeObjectURL(url)
    } catch {
      alert('Erreur lors de la génération du PDF.')
    }
  }

  const ouvrirModalLettre = (demande) => {
    const c = demande.contenu_lettre ?? {}
    setLettreForm({
      motif_lettre:    c.motif_lettre    ?? '',
      lieu_jouissance: c.lieu_jouissance ?? demande.lieu_jouissance ?? '',
      complement:      c.complement      ?? '',
    })
    setLettreError('')
    setModalDemande(demande)
  }

  const sauvegarderLettre = async () => {
    setLettreLoading(true)
    setLettreError('')
    try {
      await api.put(`/api/demandes/${modalDemande.id}/lettre`, lettreForm)
      // Rafraîchir la liste pour refléter contenu_lettre mis à jour
      charger(annee)
      setModalDemande(null)
    } catch (err) {
      setLettreError(err.response?.data?.message ?? 'Erreur lors de la sauvegarde.')
    } finally {
      setLettreLoading(false)
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
                      <span style={{
                        fontSize: 11,
                        background: d.type === 'DECISION' ? 'var(--primary-bg)' : 'var(--success-bg)',
                        color:      d.type === 'DECISION' ? 'var(--primary)'    : 'var(--success)',
                        padding: '2px 8px', borderRadius: 10, fontWeight: 600,
                      }}>
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
                      <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                        <button className="btn btn-outline btn-sm"
                          onClick={() => navigate(`/demandes/${d.id}`)}>
                          Détail
                        </button>
                        {/* Éditer la lettre : uniquement pour les congés du propriétaire */}
                        {d.type === 'CONGE' && d.agent_id === user?.id && (
                          <button className="btn btn-outline btn-sm"
                            onClick={() => ouvrirModalLettre(d)}
                            title="Personnaliser le contenu avant impression">
                            ✏️ Lettre
                          </button>
                        )}
                        {d.type === 'CONGE' && (
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

      {/* ── Modal édition contenu de la lettre ─────────────────────────────── */}
      {modalDemande && (
        <div style={{
          position: 'fixed', inset: 0, background: 'rgba(0,0,0,.45)',
          display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000,
        }}>
          <div style={{
            background: '#fff', borderRadius: 10, padding: 28,
            width: '100%', maxWidth: 520, boxShadow: '0 8px 32px rgba(0,0,0,.2)',
          }}>
            <div style={{ fontWeight: 700, fontSize: 16, marginBottom: 16 }}>
              Personnaliser la lettre de congé
            </div>
            <p style={{ fontSize: 12, color: 'var(--gray)', marginBottom: 16 }}>
              Ces champs remplacent ou complètent le contenu généré automatiquement dans le PDF.
              Laissez vide pour conserver la valeur par défaut.
            </p>

            <label style={{ display: 'block', marginBottom: 4, fontSize: 13, fontWeight: 600 }}>
              Motif personnalisé
            </label>
            <textarea
              className="form-control"
              rows={3}
              style={{ width: '100%', marginBottom: 14 }}
              value={lettreForm.motif_lettre}
              onChange={e => setLettreForm(f => ({ ...f, motif_lettre: e.target.value }))}
              placeholder="Ex : congé annuel, départ prévu le …"
            />

            <label style={{ display: 'block', marginBottom: 4, fontSize: 13, fontWeight: 600 }}>
              Lieu de jouissance (remplace la valeur par défaut)
            </label>
            <input
              className="form-control"
              style={{ width: '100%', marginBottom: 14 }}
              value={lettreForm.lieu_jouissance}
              onChange={e => setLettreForm(f => ({ ...f, lieu_jouissance: e.target.value }))}
              placeholder="Ex : Dakar, Saint-Louis…"
            />

            <label style={{ display: 'block', marginBottom: 4, fontSize: 13, fontWeight: 600 }}>
              Complément (texte libre ajouté en bas de lettre)
            </label>
            <textarea
              className="form-control"
              rows={2}
              style={{ width: '100%', marginBottom: 16 }}
              value={lettreForm.complement}
              onChange={e => setLettreForm(f => ({ ...f, complement: e.target.value }))}
              placeholder="Informations complémentaires, formule de politesse…"
            />

            {lettreError && (
              <div style={{ color: 'var(--danger)', fontSize: 13, marginBottom: 12 }}>
                {lettreError}
              </div>
            )}

            <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
              <button className="btn btn-outline" onClick={() => setModalDemande(null)}
                disabled={lettreLoading}>
                Annuler
              </button>
              <button className="btn btn-primary" onClick={sauvegarderLettre}
                disabled={lettreLoading}>
                {lettreLoading ? 'Enregistrement…' : 'Enregistrer'}
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  )
}
