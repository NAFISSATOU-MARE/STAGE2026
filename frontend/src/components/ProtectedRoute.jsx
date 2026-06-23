import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import Layout from './Layout'

export default function ProtectedRoute() {
  const { user } = useAuth()
  const location = useLocation()

  if (!user) return <Navigate to="/login" replace />

  // Forcer le changement de mot de passe à la première connexion
  if (user.must_change_password && location.pathname !== '/change-password') {
    return <Navigate to="/change-password" replace />
  }

  return <Layout><Outlet /></Layout>
}
