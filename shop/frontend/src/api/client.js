import axios from 'axios'

export const AUTH_TOKEN_KEY = 'boutique_auth_token'

const apiRoot = (import.meta.env.VITE_API_URL || '').replace(/\/+$/, '')

// Configure global axios defaults as well so views using axios directly share credentials & Bearer token
axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

const apiClient = axios.create({
  baseURL: `${apiRoot}/api`,
  withCredentials: true,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

const attachBearerInterceptor = (instance) => {
  instance.interceptors.request.use((config) => {
    const token = localStorage.getItem(AUTH_TOKEN_KEY)
    if (token) {
      config.headers = config.headers || {}
      config.headers['Authorization'] = `Bearer ${token}`
    }
    return config
  })
}

attachBearerInterceptor(apiClient)
attachBearerInterceptor(axios)

export const setAuthToken = (token) => {
  if (token) {
    localStorage.setItem(AUTH_TOKEN_KEY, token)
    apiClient.defaults.headers.common['Authorization'] = `Bearer ${token}`
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  } else {
    localStorage.removeItem(AUTH_TOKEN_KEY)
    delete apiClient.defaults.headers.common['Authorization']
    delete axios.defaults.headers.common['Authorization']
  }
}

export const getCsrfCookie = () =>
  axios.get(`${apiRoot}/sanctum/csrf-cookie`, { withCredentials: true }).catch(() => {})

export default apiClient
