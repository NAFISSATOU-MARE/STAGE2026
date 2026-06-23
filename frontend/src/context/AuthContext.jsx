import { createContext, useContext, useState, useEffect } from 'react'
import api from '../api/axios'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser]       = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem('token')
    if (!token) { setLoading(false); return }
    api.get('/api/me')
      .then(r => setUser(r.data))
      .catch(() => localStorage.removeItem('token'))
      .finally(() => setLoading(false))
  }, [])

  const login = async (email, password) => {
    const r = await api.post('/api/login', { email, password })
    localStorage.setItem('token', r.data.token)
    setUser(r.data.agent)
    return r.data.agent
  }

  const logout = async () => {
    try { await api.post('/api/logout') } catch {}
    localStorage.removeItem('token')
    setUser(null)
  }

  const refreshUser = async () => {
    const r = await api.get('/api/me')
    setUser(r.data)
  }

  return (
    <AuthContext.Provider value={{ user, login, logout, refreshUser, loading }}>
      {!loading && children}
    </AuthContext.Provider>
  )
}

export const useAuth = () => useContext(AuthContext)
