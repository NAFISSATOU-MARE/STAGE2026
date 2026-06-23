import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import ProtectedRoute       from './components/ProtectedRoute'
import LoginPage            from './pages/LoginPage'
import ChangePasswordPage   from './pages/ChangePasswordPage'
import DashboardPage        from './pages/DashboardPage'
import MesDemandesPage      from './pages/MesDemandesPage'
import NouvelleDemandeePage from './pages/NouvelleDemandeePage'
import DemandeDetailPage    from './pages/DemandeDetailPage'
import ValidationsPage      from './pages/ValidationsPage'
import AdminDashboardPage   from './pages/admin/AdminDashboardPage'
import AgentsAdminPage      from './pages/admin/AgentsAdminPage'

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          {/* Pages publiques */}
          <Route path="/login"           element={<LoginPage />} />
          <Route path="/change-password" element={<ChangePasswordPage />} />

          {/* Pages protégées */}
          <Route element={<ProtectedRoute />}>
            {/* Agents */}
            <Route path="/dashboard"     element={<DashboardPage />} />
            <Route path="/demandes"      element={<MesDemandesPage />} />
            <Route path="/demandes/new"  element={<NouvelleDemandeePage />} />
            <Route path="/demandes/:id"  element={<DemandeDetailPage />} />
            <Route path="/validations"   element={<ValidationsPage />} />

            {/* Administration */}
            <Route path="/admin/dashboard" element={<AdminDashboardPage />} />
            <Route path="/admin/agents"    element={<AgentsAdminPage />} />
          </Route>

          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}
