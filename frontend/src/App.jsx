import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import ProtectedRoute from './components/ProtectedRoute'
import LoginPage           from './pages/LoginPage'
import DashboardPage       from './pages/DashboardPage'
import MesDemandesPage     from './pages/MesDemandesPage'
import NouvelleDemandeePage from './pages/NouvelleDemandeePage'
import DemandeDetailPage   from './pages/DemandeDetailPage'
import ValidationsPage     from './pages/ValidationsPage'

export default function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route element={<ProtectedRoute />}>
            <Route path="/dashboard"    element={<DashboardPage />} />
            <Route path="/demandes"     element={<MesDemandesPage />} />
            <Route path="/demandes/new" element={<NouvelleDemandeePage />} />
            <Route path="/demandes/:id" element={<DemandeDetailPage />} />
            <Route path="/validations"  element={<ValidationsPage />} />
          </Route>
          <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  )
}
