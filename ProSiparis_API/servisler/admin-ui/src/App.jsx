import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeModeProvider, useThemeMode } from './contexts/ThemeContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ProductListPage from './pages/ProductListPage';
import ProductEditPage from './pages/ProductEditPage'; // Yeni eklendi
import { ThemeProvider, Typography } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';


// AppContent, tema context'ine erişebilmek için ayrı bir bileşen olmalı
const AppContent = () => {
  const { theme } = useThemeMode();

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <Routes>
        <Route path="/login" element={<LoginPage />} />
          <Route element={<ProtectedRoute />}>
            <Route element={<Layout />}>
              <Route path="/" element={<Navigate to="/dashboard" replace />} />
              <Route path="dashboard" element={<DashboardPage />} />
              <Route path="/admin/urunler" element={<ProductListPage />} />
              <Route path="/admin/urunler/yeni" element={<ProductEditPage />} />
              <Route path="/admin/urunler/:urunId/duzenle" element={<ProductEditPage />} />
            </Route>
        </Route>
      </Routes>
    </ThemeProvider>
  );
};

function App() {
  return (
    <Router>
      <AuthProvider>
        <ThemeModeProvider>
          <AppContent />
        </ThemeModeProvider>
      </AuthProvider>
    </Router>
  );
}

export default App;
