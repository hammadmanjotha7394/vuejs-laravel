import axios from 'axios'

const apiClient = axios.create({
  baseURL: '/api',
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export const getCsrfCookie = () => axios.get('/sanctum/csrf-cookie', { withCredentials: true })

export default apiClient
