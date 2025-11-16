import React, { createContext, useState, useContext, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import apiClient from '../api/apiClient';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [token, setToken] = useState(localStorage.getItem('authToken'));
  const navigate = useNavigate();

  useEffect(() => {
    if (token) {
      localStorage.setItem('authToken', token);
    } else {
      localStorage.removeItem('authToken');
    }
  }, [token]);

  const login = async (credentials) => {
    try {
      const response = await apiClient.post('/kullanici/giris', credentials);
      if (response.data.veri && response.data.veri.token) {
        setToken(response.data.veri.token);
        navigate('/dashboard');
      }
    } catch (error) {
      console.error("Giriş hatası:", error);
      // Hata mesajını kullanıcıya göstermek için bir state daha eklenebilir.
    }
  };

  const logout = () => {
    setToken(null);
    navigate('/login');
  };

  return (
    <AuthContext.Provider value={{ token, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  return useContext(AuthContext);
};
