import { createContext, useContext, useState, useEffect } from 'react'
import api from '../api/axios'

const AuthContext = createContext(null)

const TOKEN_KEY = 'dgb_token'

const getToken = () =>
  localStorage.getItem(TOKEN_KEY) || sessionStorage.getItem(TOKEN_KEY)

const clearToken = () => {
  localStorage.removeItem(TOKEN_KEY)
  sessionStorage.removeItem(TOKEN_KEY)
}

export function AuthProvider({ children }) {
  const [user,    setUser]    = useState(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = getToken()
    if (!token) { setLoading(false); return }
    api.get('/api/me')
      .then(r => setUser(r.data))
      .catch(() => clearToken())
      .finally(() => setLoading(false))
  }, [])

  const login = async (email, password, remember = false) => {
    const r = await api.post('/api/login', { email, password })
    const store = remember ? localStorage : sessionStorage
    store.setItem(TOKEN_KEY, r.data.token)
    setUser(r.data.agent)
    return r.data.agent
  }

  const logout = async () => {
    try { await api.post('/api/logout') } catch {}
    clearToken()
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
