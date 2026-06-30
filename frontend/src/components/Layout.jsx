import { useEffect, useRef, useState, useCallback } from 'react'
import { NavLink, useNavigate }                    from 'react-router-dom'
import { useAuth }                                 from '../context/AuthContext'
import { useTheme }                                from '../context/ThemeContext'
import { LogoNavbar }                              from './LogoDGB'
import api                                         from '../api/axios'

const VALIDATOR_ROLES = ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE']

// ─── Icônes inline (SVG) ──────────────────────────────────────────────────────
const IconSun    = () => <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
const IconMoon   = () => <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
const IconMail   = () => <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
const IconUser   = () => <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
const IconBurger = () => <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5"><line x1="3" y1="6"  x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>

// ─── Styles inline partagés ───────────────────────────────────────────────────
const navBtn = {
  background: 'none', border: 'none', cursor: 'pointer',
  color: 'rgba(255,255,255,.82)', padding: '6px 8px', borderRadius: 6,
  display: 'flex', alignItems: 'center', gap: 6, fontSize: 12,
  position: 'relative', transition: 'background .15s',
}

export default function Layout({ children }) {
  const { user, logout }    = useAuth()
  const { theme, toggleTheme } = useTheme()
  const navigate             = useNavigate()

  const [sidebarOpen,   setSidebarOpen]   = useState(false)
  const [profileOpen,   setProfileOpen]   = useState(false)
  const [notifOpen,     setNotifOpen]     = useState(false)
  const [notifications, setNotifications] = useState([])
  const [nonLues,       setNonLues]       = useState(0)

  const profileRef = useRef(null)
  const notifRef   = useRef(null)

  // Fermer les dropdowns au clic extérieur
  useEffect(() => {
    const handle = (e) => {
      if (profileRef.current && !profileRef.current.contains(e.target)) setProfileOpen(false)
      if (notifRef.current   && !notifRef.current.contains(e.target))   setNotifOpen(false)
    }
    document.addEventListener('mousedown', handle)
    return () => document.removeEventListener('mousedown', handle)
  }, [])

  // Polling notifications (toutes les 30 s)
  const chargerNotifications = useCallback(async () => {
    try {
      const r = await api.get('/api/notifications')
      setNotifications(r.data.notifications)
      setNonLues(r.data.non_lues)
    } catch {}
  }, [])

  useEffect(() => {
    chargerNotifications()
    const id = setInterval(chargerNotifications, 30_000)
    return () => clearInterval(id)
  }, [chargerNotifications])

  const marquerLue = async (notif) => {
    if (!notif.lu) {
      try {
        await api.put(`/api/notifications/${notif.id}/lire`)
        setNotifications(ns => ns.map(n => n.id === notif.id ? { ...n, lu: true } : n))
        setNonLues(c => Math.max(0, c - 1))
      } catch {}
    }
    if (notif.demande_id) navigate(`/demandes/${notif.demande_id}`)
    setNotifOpen(false)
  }

  const marquerToutesLues = async () => {
    try {
      await api.put('/api/notifications/lire-tout')
      setNotifications(ns => ns.map(n => ({ ...n, lu: true })))
      setNonLues(0)
    } catch {}
  }

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const isValidator = VALIDATOR_ROLES.includes(user?.role)
  const isAdmin     = user?.role === 'ADMIN'
  const isDirAdmin  = user?.role === 'ADMIN_DIRECTION'
  const isAnyAdmin  = isAdmin || isDirAdmin
  const link        = ({ isActive }) => isActive ? 'active' : ''

  // Initiales de l'avatar
  const initiales = `${user?.prenom?.[0] ?? ''}${user?.nom?.[0] ?? ''}`.toUpperCase()

  const typeLabel = {
    VALIDATION_REQUISE: { icon: '📋', color: '#3b82f6' },
    VALIDATION_RECUE:   { icon: '✅', color: '#16a34a' },
    REJET_RECU:         { icon: '❌', color: '#dc2626' },
    COMPTE_CREE:        { icon: '🎉', color: '#f59e0b' },
  }

  return (
    <div className="layout">

      {/* ══ Navbar ══ */}
      <nav className="navbar">

        {/* Burger (mobile) */}
        <button
          className="burger-btn"
          aria-label="Menu"
          onClick={() => setSidebarOpen(o => !o)}
        >
          <IconBurger />
        </button>

        <LogoNavbar />

        {/* Actions droite */}
        <div style={{ display: 'flex', alignItems: 'center', gap: 6, marginLeft: 'auto' }}>

          {/* Toggle thème */}
          <button
            data-testid="theme-toggle"
            onClick={toggleTheme}
            style={{ ...navBtn }}
            title={theme === 'light' ? 'Mode sombre' : 'Mode clair'}
            aria-label="Basculer le thème"
          >
            {theme === 'light' ? <IconMoon /> : <IconSun />}
          </button>

          {/* Notifications */}
          <div ref={notifRef} style={{ position: 'relative' }}>
            <button
              data-testid="notif-btn"
              style={{ ...navBtn }}
              onClick={() => { setNotifOpen(o => !o); setProfileOpen(false) }}
              aria-label="Notifications"
              title="Notifications"
            >
              <IconMail />
              {nonLues > 0 && (
                <span
                  data-testid="notif-dot"
                  style={{
                    position: 'absolute', top: 4, right: 4,
                    width: 9, height: 9, background: '#3b82f6',
                    borderRadius: '50%', border: '2px solid var(--primary)',
                  }}
                />
              )}
            </button>

            {notifOpen && (
              <div style={{
                position: 'absolute', top: 'calc(100% + 8px)', right: 0,
                width: 340, maxHeight: 420, overflowY: 'auto',
                background: 'var(--white)', border: '1px solid var(--border)',
                borderRadius: 10, boxShadow: 'var(--shadow-lg)', zIndex: 999,
                animation: 'slideUp .15s ease',
              }}>
                <div style={{
                  padding: '12px 16px', borderBottom: '1px solid var(--border)',
                  display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                }}>
                  <span style={{ fontWeight: 700, fontSize: 13, color: 'var(--primary)' }}>
                    Notifications {nonLues > 0 && <span style={{ color: '#3b82f6' }}>({nonLues})</span>}
                  </span>
                  {nonLues > 0 && (
                    <button onClick={marquerToutesLues}
                      style={{ background: 'none', border: 'none', cursor: 'pointer',
                               fontSize: 11, color: 'var(--gray)', textDecoration: 'underline' }}>
                      Tout marquer lu
                    </button>
                  )}
                </div>

                {notifications.length === 0 ? (
                  <div style={{ padding: '28px 16px', textAlign: 'center', color: 'var(--gray-lt)', fontSize: 13 }}>
                    Aucune notification
                  </div>
                ) : notifications.map(n => (
                  <div key={n.id}
                    onClick={() => marquerLue(n)}
                    style={{
                      padding: '10px 16px', cursor: n.demande_id ? 'pointer' : 'default',
                      borderBottom: '1px solid var(--border)',
                      background: n.lu ? 'transparent' : 'rgba(59,130,246,.06)',
                      display: 'flex', gap: 10, alignItems: 'flex-start',
                      transition: 'background .1s',
                    }}
                  >
                    <span style={{ fontSize: 18, flexShrink: 0 }}>
                      {typeLabel[n.type]?.icon ?? '🔔'}
                    </span>
                    <div style={{ flex: 1, minWidth: 0 }}>
                      <div style={{ fontSize: 12, color: 'var(--text)', lineHeight: 1.4,
                                    fontWeight: n.lu ? 400 : 600 }}>
                        {n.message}
                      </div>
                      <div style={{ fontSize: 10, color: 'var(--gray-lt)', marginTop: 2 }}>
                        {new Date(n.created_at).toLocaleString('fr-FR')}
                      </div>
                    </div>
                    {!n.lu && (
                      <div style={{ width: 8, height: 8, background: '#3b82f6',
                                    borderRadius: '50%', flexShrink: 0, marginTop: 4 }} />
                    )}
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Profil */}
          <div ref={profileRef} style={{ position: 'relative' }}>
            <button
              data-testid="profile-btn"
              onClick={() => { setProfileOpen(o => !o); setNotifOpen(false) }}
              style={{
                ...navBtn,
                background: profileOpen ? 'rgba(255,255,255,.12)' : 'none',
              }}
              aria-label="Mon profil"
            >
              {/* Avatar initiales */}
              <div style={{
                width: 32, height: 32, borderRadius: '50%',
                background: 'rgba(255,255,255,.2)',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 12, fontWeight: 700, color: '#fff', flexShrink: 0,
              }}>
                {initiales || <IconUser />}
              </div>
              <div style={{ textAlign: 'left', lineHeight: 1.3 }} className="navbar-user-name">
                <div style={{ fontSize: 12, fontWeight: 600 }}>{user?.prenom} {user?.nom}</div>
                <div style={{ fontSize: 10, color: 'rgba(255,255,255,.55)' }}>
                  {user?.direction?.sigle ? `${user.direction.sigle} · ` : ''}{user?.role}
                </div>
              </div>
            </button>

            {profileOpen && (
              <div style={{
                position: 'absolute', top: 'calc(100% + 8px)', right: 0,
                width: 270, background: 'var(--white)',
                border: '1px solid var(--border)',
                borderRadius: 10, boxShadow: 'var(--shadow-lg)', zIndex: 999,
                animation: 'slideUp .15s ease',
              }}>
                {/* En-tête avatar */}
                <div style={{
                  padding: '16px', borderBottom: '1px solid var(--border)',
                  display: 'flex', alignItems: 'center', gap: 12,
                }}>
                  <div style={{
                    width: 44, height: 44, borderRadius: '50%',
                    background: 'var(--primary)', color: '#fff',
                    display: 'flex', alignItems: 'center', justifyContent: 'center',
                    fontSize: 16, fontWeight: 700, flexShrink: 0,
                  }}>
                    {initiales || '?'}
                  </div>
                  <div>
                    <div style={{ fontWeight: 700, fontSize: 14, color: 'var(--primary)' }}>
                      {user?.prenom} {user?.nom}
                    </div>
                    <div style={{ fontSize: 11, color: 'var(--gray)', marginTop: 2 }}>
                      {user?.role}
                    </div>
                  </div>
                </div>

                {/* Infos */}
                <div style={{ padding: '12px 16px', fontSize: 12 }}>
                  {[
                    ['Poste',      user?.poste],
                    ['Matricule',  user?.matricule],
                    ['Téléphone',  user?.telephone],
                    ['Direction',  user?.direction?.nom],
                    ['Division',   user?.division?.nom],
                  ].filter(([, v]) => v).map(([label, val]) => (
                    <div key={label} style={{
                      display: 'flex', gap: 8, marginBottom: 6,
                      paddingBottom: 6, borderBottom: '1px solid var(--border)',
                    }}>
                      <span style={{ color: 'var(--gray)', minWidth: 80 }}>{label} :</span>
                      <span style={{ fontWeight: 500, color: 'var(--text)', wordBreak: 'break-word' }}>{val}</span>
                    </div>
                  ))}
                </div>

                {/* Déconnexion */}
                <div style={{ padding: '8px 12px 12px' }}>
                  <button
                    onClick={handleLogout}
                    style={{
                      width: '100%', padding: '9px', borderRadius: 8,
                      background: 'var(--danger-bg)', color: 'var(--danger)',
                      border: '1px solid rgba(185,28,28,.2)',
                      cursor: 'pointer', fontSize: 13, fontWeight: 600,
                      fontFamily: 'var(--font)',
                    }}
                  >
                    Déconnexion
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      </nav>

      {/* ══ Corps : sidebar + main ══ */}
      <div className="body-wrap">

        {/* Overlay mobile (ferme la sidebar) */}
        <div
          className={`sidebar-overlay${sidebarOpen ? ' open' : ''}`}
          onClick={() => setSidebarOpen(false)}
        />

        {/* ══ Sidebar sombre ══ */}
        <aside className={`sidebar${sidebarOpen ? ' open' : ''}`}>

          {isAdmin && (
            <>
              <p className="sidebar-section">Administration</p>
              <NavLink to="/admin/dashboard" className={link} onClick={() => setSidebarOpen(false)}>📊 Tableau de bord</NavLink>
              <NavLink to="/admin/agents"    className={link} onClick={() => setSidebarOpen(false)}>👥 Agents</NavLink>
            </>
          )}

          {isDirAdmin && (
            <>
              <p className="sidebar-section">Ma direction — {user?.direction?.sigle}</p>
              <NavLink to="/admin/dashboard" className={link} onClick={() => setSidebarOpen(false)}>📊 Tableau de bord</NavLink>
              <NavLink to="/admin/agents"    className={link} onClick={() => setSidebarOpen(false)}>👥 Agents</NavLink>
            </>
          )}

          {!isAnyAdmin && (
            <>
              <p className="sidebar-section">Général</p>
              <NavLink to="/dashboard" className={link} onClick={() => setSidebarOpen(false)}>🏠 Tableau de bord</NavLink>

              <p className="sidebar-section">Mes demandes</p>
              <NavLink to="/demandes"     end={false} className={link} onClick={() => setSidebarOpen(false)}>📋 Historique</NavLink>
              <NavLink to="/demandes/new" className={link} onClick={() => setSidebarOpen(false)}>✏️ Nouvelle demande</NavLink>

              {isValidator && (
                <>
                  <p className="sidebar-section">Validation</p>
                  <NavLink to="/validations" className={link} onClick={() => setSidebarOpen(false)}>
                    ✅ À valider
                    {nonLues > 0 && (
                      <span style={{
                        marginLeft: 'auto', background: '#3b82f6', color: '#fff',
                        fontSize: 10, fontWeight: 700, padding: '1px 6px',
                        borderRadius: 10, lineHeight: 1.6,
                      }}>
                        {nonLues}
                      </span>
                    )}
                  </NavLink>
                </>
              )}
            </>
          )}

          <div className="sidebar-footer">
            <div className="sidebar-tricolor" />
          </div>
        </aside>

        {/* ══ Contenu principal ══ */}
        <main className="main">{children}</main>
      </div>
    </div>
  )
}
