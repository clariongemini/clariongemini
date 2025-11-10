import axios from 'axios';

const apiClient = axios.create({
  baseURL: '/api', // Gateway-Servisi'ne yönlendirilen base URL
});

// Axios interceptor'ı ile her isteğe token ekle
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('authToken');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

export default apiClient;
