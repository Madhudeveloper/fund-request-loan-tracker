import axios from 'axios';
const api = axios.create({ baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api' });
api.interceptors.request.use(c => {
  const t = localStorage.getItem('token');
  if (t) c.headers.Authorization = `Bearer ${t}`;
  c.headers.Accept = 'application/json';
  return c;
});
api.interceptors.response.use(r => r, e => {
  if (e.response?.status === 401 && !e.config.url.includes('/login')) { localStorage.clear(); window.location.href = '/login'; }
  return Promise.reject(e);
});
export const errMsg = e => {
  const d = e.response?.data;
  return d?.errors ? Object.values(d.errors).flat().join(' ') : d?.message || 'Something went wrong. Please try again.';
};
export default api;
