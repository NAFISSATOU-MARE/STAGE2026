import { NavLink, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'

const VALIDATOR_ROLES = ['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH']

export default function Layout({ children }) {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  const isValidator = VALIDATOR_ROLES.includes(user?.role)

  return (
    <div className="layout">
      {/* ── Navbar ── */}
      <nav className="navbar">
        <div className="navbar-brand">
          ⊙ DGB <span>Gestion des congés et décisions</span>
        </div>
        <div className="navbar-user">
          <span>{user?.prenom} {user?.nom}</span>
          <span style={{ color: '#7fb3f5', fontSize: 11 }}>
            {user?.direction?.sigle} — {user?.role}
          </span>
          <button className="btn-logout" onClick={handleLogout}>Déconnexion</button>
        </div>
      </nav>

      <div className="body-wrap">
        {/* ── Sidebar ── */}
        <aside className="sidebar">
          <p className="sidebar-section">Général</p>
          <NavLink to="/dashboard"  className={({ isActive }) => isActive ? 'active' : ''}>
            🏠 Tableau de bord
          </NavLink>

          <p className="sidebar-section">Mes demandes</p>
          <NavLink to="/demandes"     className={({ isActive }) => isActive ? 'active' : ''}>
            📋 Historique
          </NavLink>
          <NavLink to="/demandes/new" className={({ isActive }) => isActive ? 'active' : ''}>
            ✏️ Nouvelle demande
          </NavLink>

          {isValidator && (
            <>
              <p className="sidebar-section">Validation</p>
              <NavLink to="/validations" className={({ isActive }) => isActive ? 'active' : ''}>
                ✅ À valider
              </NavLink>
            </>
          )}
        </aside>

        {/* ── Contenu principal ── */}
        <main className="main">{children}</main>
      </div>
    </div>
  )
}
