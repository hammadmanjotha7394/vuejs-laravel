import axios from 'axios'

const apiRoot = (import.meta.env.VITE_API_URL || '').replace(/\/+$/, '')

const apiClient = axios.create({
  baseURL: `${apiRoot}/api`,
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export const getCsrfCookie = () =>
  axios.get(`${apiRoot}/sanctum/csrf-cookie`, { withCredentials: true })

export default apiClient

