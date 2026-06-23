import { useEffect, useState } from 'react'
import api from '../../api/axios'

const ROLES   = ['AGENT', 'CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'ADMIN']
const PROFILS = ['CONTRACTUEL', 'AGENT_ETAT']

const ROLE_COLOR = {
  AGENT:         { bg: '#f0f0f0', color: '#555' },
  CHEF_DIVISION: { bg: '#dce8ff', color: '#1a3a8f' },
  DIRECTEUR:     { bg: '#ccd8f5', color: '#0D2157' },
  DAP:           { bg: '#ead5ff', color: '#6b21a8' },
  DRH:           { bg: '#fde4c8', color: '#9a3412' },
  ADMIN:         { bg: '#fde8e8', color: '#c0392b' },
}

const INIT = {
  nom: '', prenom: '', email: '', password: '',
  direction_id: '', division_id: '',
  poste: '', corps: '', profil: 'CONTRACTUEL',
  matricule: '', role: 'AGENT',
}

export default function AgentsAdminPage() {
  const [agents,     setAgents]     = useState([])
  const [directions, setDirections] = useState([])
  const [loading,    setLoading]    = useState(true)
  const [search,     setSearch]     = useState('')
  const [filterRole, setFilterRole] = useState('')
  const [modal,      setModal]      = useState(null)  // null | 'create' | agent
  const [form,       setForm]       = useState(INIT)
  const [sending,    setSending]    = useState(false)
  const [error,      setError]      = useState('')

  const charger = () => {
    setLoading(true)
    Promise.all([
      api.get('/api/admin/agents'),
      api.get('/api/directions'),
    ]).then(([a, d]) => {
      setAgents(a.data)
      setDirections(d.data)
    }).finally(() => setLoading(false))
  }

  useEffect(() => { charger() }, [])

  const ouvrirCreation = () => {
    setForm(INIT)
    setError('')
    setModal('create')
  }

  const ouvrirEdition = a => {
    setForm({
      nom:          a.nom,
      prenom:       a.prenom,
      email:        a.email,
      password:     '',
      direction_id: a.direction_id ?? '',
      division_id:  a.division_id  ?? '',
      poste:        a.poste,
      corps:        a.corps        ?? '',
      profil:       a.profil,
      matricule:    a.matricule    ?? '',
      role:         a.role,
    })
    setError('')
    setModal(a)
  }

  const fermer = () => { setModal(null); setError('') }

  const set = field => e => setForm(f => ({ ...f, [field]: e.target.value }))

  const handleDir = e => setForm(f => ({ ...f, direction_id: e.target.value, division_id: '' }))

  const divisions = form.direction_id
    ? (directions.find(d => d.id == form.direction_id)?.divisions ?? [])
    : []

  const besoinDirDiv = form.role !== 'ADMIN'

  const soumettre = async e => {
    e.preventDefault()
    setSending(true)
    setError('')
    try {
      const payload = {
        ...form,
        direction_id: form.direction_id || null,
        division_id:  form.division_id  || null,
        corps:        form.corps        || null,
        matricule:    form.matricule    || null,
      }
      if (!payload.password) delete payload.password

      if (modal === 'create') {
        await api.post('/api/admin/agents', payload)
      } else {
        await api.put(`/api/admin/agents/${modal.id}`, payload)
      }
      fermer()
      charger()
    } catch (err) {
      const errs = err.response?.data?.errors
      setError(errs
        ? Object.values(errs).flat().join(' · ')
        : err.response?.data?.message ?? 'Erreur lors de l\'enregistrement.'
      )
    } finally {
      setSending(false)
    }
  }

  const supprimer = async a => {
    if (!window.confirm(`Supprimer ${a.prenom} ${a.nom} ?\nCette action est irréversible.`)) return
    try {
      await api.delete(`/api/admin/agents/${a.id}`)
      charger()
    } catch (err) {
      alert(err.response?.data?.message ?? 'Erreur lors de la suppression.')
    }
  }

  const filtres = agents.filter(a => {
    const t = search.toLowerCase()
    const ok = !t || [a.nom, a.prenom, a.email, a.direction?.sigle ?? '']
      .some(v => v.toLowerCase().includes(t))
    return ok && (!filterRole || a.role === filterRole)
  })

  const RoleBadge = ({ role }) => {
    const c = ROLE_COLOR[role] ?? { bg: '#eee', color: '#333' }
    return (
      <span style={{
        fontSize: 11, padding: '2px 8px', borderRadius: 10,
        fontWeight: 700, background: c.bg, color: c.color,
      }}>
        {role}
      </span>
    )
  }

  return (
    <>
      <div className="page-header">
        <div>
          <div className="page-title">Gestion des agents</div>
          <div style={{ fontSize: 13, color: 'var(--gray)', marginTop: 2 }}>
            {agents.length} agent(s) enregistré(s)
          </div>
        </div>
        <button className="btn btn-primary" onClick={ouvrirCreation}>
          + Nouvel agent
        </button>
      </div>

      <div className="card">
        {/* Filtres */}
        <div style={{ display: 'flex', gap: 12, marginBottom: 16 }}>
          <input
            className="form-control"
            style={{ maxWidth: 300 }}
            placeholder="Rechercher nom, email, direction…"
            value={search}
            onChange={e => setSearch(e.target.value)}
          />
          <select className="form-control" style={{ width: 170 }}
            value={filterRole} onChange={e => setFilterRole(e.target.value)}>
            <option value="">Tous les rôles</option>
            {ROLES.map(r => <option key={r} value={r}>{r}</option>)}
          </select>
          {(search || filterRole) && (
            <button className="btn btn-outline btn-sm"
              onClick={() => { setSearch(''); setFilterRole('') }}>
              Effacer
            </button>
          )}
        </div>

        {loading ? (
          <div className="empty-state">Chargement…</div>
        ) : filtres.length === 0 ? (
          <div className="empty-state">Aucun agent trouvé.</div>
        ) : (
          <div className="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Nom complet</th>
                  <th>Email</th>
                  <th>Direction</th>
                  <th>Division</th>
                  <th>Profil</th>
                  <th>Rôle</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {filtres.map(a => (
                  <tr key={a.id}>
                    <td>
                      <div style={{ fontWeight: 600 }}>{a.prenom} {a.nom}</div>
                      {a.poste && <div style={{ fontSize: 11, color: 'var(--gray)' }}>{a.poste}</div>}
                    </td>
                    <td style={{ fontSize: 12, fontFamily: 'monospace' }}>{a.email}</td>
                    <td>{a.direction?.sigle ?? '—'}</td>
                    <td style={{ fontSize: 12 }}>{a.division?.sigle ?? '—'}</td>
                    <td>
                      <span style={{
                        fontSize: 11, padding: '2px 7px', borderRadius: 10, fontWeight: 600,
                        background: a.profil === 'AGENT_ETAT' ? 'var(--primary-bg)' : '#f0f0f0',
                        color:      a.profil === 'AGENT_ETAT' ? 'var(--primary)'    : '#555',
                      }}>
                        {a.profil === 'AGENT_ETAT' ? 'Fonctionnaire' : 'Contractuel'}
                      </span>
                    </td>
                    <td><RoleBadge role={a.role} /></td>
                    <td>
                      <div style={{ display: 'flex', gap: 6 }}>
                        <button className="btn btn-outline btn-sm" onClick={() => ouvrirEdition(a)}>
                          Modifier
                        </button>
                        <button className="btn btn-danger btn-sm" onClick={() => supprimer(a)}>
                          Supprimer
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* ── Modal création / édition ── */}
      {modal !== null && (
        <div className="modal-overlay"
          onClick={e => { if (e.target === e.currentTarget) fermer() }}>
          <div className="modal-box"
            style={{ width: 600, maxHeight: '92vh', overflowY: 'auto' }}>

            <div className="modal-title">
              {modal === 'create'
                ? '+ Nouvel agent'
                : `Modifier — ${modal.prenom} ${modal.nom}`}
            </div>

            {error && <div className="alert alert-error">{error}</div>}

            <form onSubmit={soumettre}>

              <div className="form-grid">
                <div className="form-group">
                  <label>Prénom *</label>
                  <input className="form-control" value={form.prenom}
                    onChange={set('prenom')} required />
                </div>
                <div className="form-group">
                  <label>Nom *</label>
                  <input className="form-control" value={form.nom}
                    onChange={set('nom')} required />
                </div>
              </div>

              <div className="form-group">
                <label>Adresse e-mail *</label>
                <input type="email" className="form-control" value={form.email}
                  onChange={set('email')} required />
              </div>

              <div className="form-group">
                <label>
                  {modal === 'create'
                    ? 'Mot de passe *'
                    : 'Nouveau mot de passe (laisser vide = inchangé)'}
                </label>
                <input type="password" className="form-control" value={form.password}
                  onChange={set('password')} required={modal === 'create'}
                  placeholder={modal === 'create' ? '' : '••••••••'} />
              </div>

              <div className="form-group">
                <label>Rôle *</label>
                <select className="form-control" value={form.role} onChange={set('role')}>
                  {ROLES.map(r => <option key={r} value={r}>{r}</option>)}
                </select>
                {form.role === 'ADMIN' && (
                  <p style={{ fontSize: 11, color: 'var(--dgb-green)', marginTop: 4 }}>
                    Un administrateur n'est pas rattaché à une direction/division.
                  </p>
                )}
              </div>

              <div className="form-grid">
                <div className="form-group">
                  <label>Direction {besoinDirDiv ? '*' : ''}</label>
                  <select className="form-control" value={form.direction_id}
                    onChange={handleDir} required={besoinDirDiv}>
                    <option value="">— Sélectionner —</option>
                    {directions.map(d => (
                      <option key={d.id} value={d.id}>{d.sigle} — {d.nom}</option>
                    ))}
                  </select>
                </div>
                <div className="form-group">
                  <label>Division {besoinDirDiv ? '*' : ''}</label>
                  <select className="form-control" value={form.division_id}
                    onChange={set('division_id')}
                    required={besoinDirDiv}
                    disabled={!form.direction_id}>
                    <option value="">— Sélectionner —</option>
                    {divisions.map(d => (
                      <option key={d.id} value={d.id}>{d.sigle} — {d.nom}</option>
                    ))}
                  </select>
                </div>
              </div>

              <div className="form-group">
                <label>Poste / Fonction *</label>
                <input className="form-control" value={form.poste}
                  onChange={set('poste')} required />
              </div>

              <div className="form-grid">
                <div className="form-group">
                  <label>Profil *</label>
                  <select className="form-control" value={form.profil} onChange={set('profil')}>
                    <option value="CONTRACTUEL">Contractuel</option>
                    <option value="AGENT_ETAT">Fonctionnaire</option>
                  </select>
                </div>
                <div className="form-group">
                  <label>Corps</label>
                  <input className="form-control" value={form.corps}
                    onChange={set('corps')} placeholder="ex: Ingénieur, Comptable…" />
                </div>
              </div>

              <div className="form-group">
                <label>Matricule</label>
                <input className="form-control" value={form.matricule}
                  onChange={set('matricule')} placeholder="ex: MAT-DSI-001" />
              </div>

              <div className="modal-actions">
                <button type="button" className="btn btn-outline" onClick={fermer}>
                  Annuler
                </button>
                <button type="submit" className="btn btn-primary" disabled={sending}>
                  {sending
                    ? <span className="spinner" />
                    : modal === 'create' ? 'Créer l\'agent' : 'Enregistrer'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  )
}
