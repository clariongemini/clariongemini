import React from 'react';
import { Outlet, Link as RouterLink } from 'react-router-dom';
import { AppBar, Toolbar, Typography, Box, Button } from '@mui/material';
import ThemeToggleButton from './ThemeToggleButton';
import { useAuth } from '../contexts/AuthContext';

const Layout = () => {
  const { logout } = useAuth();

  return (
    <Box sx={{ display: 'flex' }}>
      <AppBar position="fixed">
        <Toolbar>
          <Typography variant="h6" noWrap component="div" sx={{ flexGrow: 1 }}>
            ProSiparis Yönetim Paneli
          </Typography>
          <Button color="inherit" component={RouterLink} to="/">
            Dashboard
          </Button>
          <Button color="inherit" component={RouterLink} to="/admin/urunler">
            Ürünler
          </Button>
          <Button color="inherit" component={RouterLink} to="/admin/sayfalar">
            Sayfa Yönetimi
          </Button>
          <Button color="inherit" component={RouterLink} to="/admin/bannerlar">
            Banner Yönetimi
          </Button>
          <Button color="inherit" component={RouterLink} to="/admin/depolar">
            Depo Yönetimi
          </Button>
          <ThemeToggleButton />
          <Button color="inherit" onClick={logout} sx={{ ml: 2 }}>
            Çıkış Yap
          </Button>
        </Toolbar>
      </AppBar>
      <Box component="main" sx={{ flexGrow: 1, p: 3, mt: 8 }}>
        <Outlet />
      </Box>
    </Box>
  );
};

export default Layout;
