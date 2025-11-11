import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeModeProvider, useThemeMode } from './contexts/ThemeContext';
import ProtectedRoute from './components/ProtectedRoute';
import Layout from './components/Layout';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import ProductListPage from './pages/ProductListPage';
import ProductEditPage from './pages/ProductEditPage';
import PageListPage from './pages/PageListPage';
import PageEditPage from './pages/PageEditPage';
import BannerListPage from './pages/BannerListPage';
import BannerEditPage from './pages/BannerEditPage';
import DepoListPage from './pages/DepoListPage';
import DepoEditPage from './pages/DepoEditPage';
import MediaGalleryPage from './pages/MediaGalleryPage';
import { ThemeProvider, Typography } from '@mui/material/styles';
import CssBaseline from '@mui/material/CssBaseline';
import OrderListPage from './pages/OrderListPage';
import OrderDetailPage from './pages/OrderDetailPage'; // Yeni eklendi


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

              {/* Sayfa Yönetimi Rotaları */}
              <Route path="/admin/sayfalar" element={<PageListPage />} />
              <Route path="/admin/sayfalar/yeni" element={<PageEditPage />} />
              <Route path="/admin/sayfalar/:sayfaId/duzenle" element={<PageEditPage />} />

              {/* Banner Yönetimi Rotaları */}
              <Route path="/admin/bannerlar" element={<BannerListPage />} />
              <Route path="/admin/bannerlar/yeni" element={<BannerEditPage />} />
              <Route path="/admin/bannerlar/:bannerId/duzenle" element={<BannerEditPage />} />

              {/* Depo Yönetimi Rotaları */}
              <Route path="/admin/depolar" element={<DepoListPage />} />
              <Route path="/admin/depolar/yeni" element={<DepoEditPage />} />
              <Route path="/admin/depolar/:depoId/duzenle" element={<DepoEditPage />} />

              {/* Medya Kütüphanesi Rotası */}
              <Route path="/admin/medya" element={<MediaGalleryPage />} />

              {/* Sipariş Yönetimi Rotaları */}
              <Route path="/admin/siparisler" element={<OrderListPage />} />
              <Route path="/admin/siparisler/:siparisId" element={<OrderDetailPage />} />
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
