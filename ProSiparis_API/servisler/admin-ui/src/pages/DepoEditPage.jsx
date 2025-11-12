import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box,
  Button,
  TextField,
  Typography,
  Paper,
  Grid,
} from '@mui/material';
import apiClient from '../api/apiClient';

const DepoEditPage = () => {
  const { depoId } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(depoId);

  const [depo, setDepo] = useState({
    depo_adi: '',
    depo_kodu: '',
    adres: '',
    il: '',
    ilce: '',
  });

  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      const fetchDepo = async () => {
        try {
          setLoading(true);
          const response = await apiClient.get(`/api/admin/organizasyon/depolar/${depoId}`);
          setDepo(response.data.veri);
        } catch (error) {
          console.error("Depo bilgileri yüklenirken hata oluştu:", error);
        } finally {
          setLoading(false);
        }
      };
      fetchDepo();
    }
  }, [depoId, isEditMode]);

  const handleChange = (event) => {
    const { name, value } = event.target;
    setDepo((prevDepo) => ({
      ...prevDepo,
      [name]: value,
    }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    try {
      if (isEditMode) {
        await apiClient.put(`/api/admin/organizasyon/depolar/${depoId}`, depo);
      } else {
        await apiClient.post('/api/admin/organizasyon/depolar', depo);
      }
      navigate('/admin/depolar');
    } catch (error)_ {
      console.error("Depo kaydedilirken hata oluştu:", error.response?.data?.mesaj || error.message);
      // Kullanıcıya hata mesajı gösterilebilir.
    } finally {
      setLoading(false);
    }
  };

  return (
    <Paper sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        {isEditMode ? 'Depoyu Düzenle' : 'Yeni Depo Ekle'}
      </Typography>
      <Box component="form" noValidate autoComplete="off" onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          <Grid item xs={12} sm={6}>
            <TextField name="depo_adi" label="Depo Adı" value={depo.depo_adi} onChange={handleChange} fullWidth required />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="depo_kodu" label="Depo Kodu" value={depo.depo_kodu} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12}>
            <TextField name="adres" label="Adres" value={depo.adres} onChange={handleChange} fullWidth multiline rows={3} />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="il" label="İl" value={depo.il} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="ilce" label="İlçe" value={depo.ilce} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12} sx={{ mt: 2, display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
            <Button variant="outlined" onClick={() => navigate('/admin/depolar')}>
              İptal
            </Button>
            <Button type="submit" variant="contained" disabled={loading}>
              {loading ? 'Kaydediliyor...' : 'Kaydet'}
            </Button>
          </Grid>
        </Grid>
      </Box>
    </Paper>
  );
};

export default DepoEditPage;
