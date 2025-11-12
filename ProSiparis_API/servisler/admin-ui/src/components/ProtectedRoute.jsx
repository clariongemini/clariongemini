import React from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';

const ProtectedRoute = () => {
  const { token } = useAuth();

  if (!token) {
    // Kullanıcı giriş yapmamış, login sayfasına yönlendir
    return <Navigate to="/login" />;
  }

  // Kullanıcı giriş yapmış, istenen sayfayı göster
  return <Outlet />;
};

export default ProtectedRoute;
