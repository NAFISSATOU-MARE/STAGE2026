import { NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { LogoNavbar, LogoSidebar } from './LogoDGB'

const VALIDATOR_ROLES = ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'DGB', 'MINISTRE']

export default function Layout({ children }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const isValidator     = VALIDATOR_ROLES.includes(user?.role)
  const isAdmin         = user?.role === 'ADMIN'
  const isDirAdmin      = user?.role === 'ADMIN_DIRECTION'
  const isAnyAdmin      = isAdmin || isDirAdmin

  const link = ({ isActive }) => isActive ? 'active' : ''

  return (
    <div className="layout">

      {/* ══ Navbar ══ */}
      <nav className="navbar">
        <LogoNavbar />

        <div className="navbar-user">
          <span className="navbar-user-name">{user?.prenom} {user?.nom}</span>
          <span className="navbar-user-role">
            {user?.direction?.sigle ? `${user.direction.sigle} · ` : ''}
            {user?.role}
          </span>
          <button className="btn-logout" onClick={handleLogout}>Déconnexion</button>
        </div>
      </nav>

      <div className="body-wrap">

        {/* ══ Sidebar sombre ══ */}
        <aside className="sidebar">


          {/* ── Menu ADMIN global ── */}
          {isAdmin && (
            <>
              <p className="sidebar-section">Administration</p>
              <NavLink to="/admin/dashboard" className={link}>📊 Tableau de bord</NavLink>
              <NavLink to="/admin/agents"    className={link}>👥 Agents</NavLink>
            </>
          )}

          {/* ── Menu ADMIN direction ── */}
          {isDirAdmin && (
            <>
              <p className="sidebar-section">
                Ma direction — {user?.direction?.sigle}
              </p>
              <NavLink to="/admin/dashboard" className={link}>📊 Tableau de bord</NavLink>
              <NavLink to="/admin/agents"    className={link}>👥 Agents</NavLink>
            </>
          )}

          {/* ── Menu AGENT / VALIDEUR ── */}
          {!isAnyAdmin && (
            <>
              <p className="sidebar-section">Général</p>
              <NavLink to="/dashboard" className={link}>🏠 Tableau de bord</NavLink>

              <p className="sidebar-section">Mes demandes</p>
              <NavLink to="/demandes"     end={false} className={link}>📋 Historique</NavLink>
              <NavLink to="/demandes/new" className={link}>✏️ Nouvelle demande</NavLink>

              {isValidator && (
                <>
                  <p className="sidebar-section">Validation</p>
                  <NavLink to="/validations" className={link}>✅ À valider</NavLink>
                </>
              )}
            </>
          )}

          {/* ── Pied sidebar ── */}
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
