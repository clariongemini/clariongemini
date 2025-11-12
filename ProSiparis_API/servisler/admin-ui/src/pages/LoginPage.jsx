import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { Button, TextField, Container, Paper, Typography, Grid, Box } from '@mui/material';

const LoginPage = () => {
  const [credentials, setCredentials] = useState({ eposta: '', parola: '' });
  const { login } = useAuth();

  const handleChange = (e) => {
    const { name, value } = e.target;
    setCredentials((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    login(credentials);
  };

  return (
    <Container component="main" maxWidth="xs">
      <Paper elevation={3} sx={{ mt: 8, p: 4, display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
        <Typography component="h1" variant="h5">
          Yönetim Paneli Girişi
        </Typography>
        <Box component="form" onSubmit={handleSubmit} sx={{ mt: 1 }}>
          <TextField
            margin="normal"
            required
            fullWidth
            id="eposta"
            label="E-posta Adresi"
            name="eposta"
            autoComplete="email"
            autoFocus
            value={credentials.eposta}
            onChange={handleChange}
          />
          <TextField
            margin="normal"
            required
            fullWidth
            name="parola"
            label="Parola"
            type="password"
            id="parola"
            autoComplete="current-password"
            value={credentials.parola}
            onChange={handleChange}
          />
          <Button
            type="submit"
            fullWidth
            variant="contained"
            sx={{ mt: 3, mb: 2 }}
          >
            Giriş Yap
          </Button>
        </Box>
      </Paper>
    </Container>
  );
};

export default LoginPage;
