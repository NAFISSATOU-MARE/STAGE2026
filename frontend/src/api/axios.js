import axios from 'axios'

const api = axios.create({
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
})

// Vérifie les deux stockages (remember me ou session)
const getToken = () =>
  localStorage.getItem('dgb_token') || sessionStorage.getItem('dgb_token')

api.interceptors.request.use(config => {
  const token = getToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

export default api
