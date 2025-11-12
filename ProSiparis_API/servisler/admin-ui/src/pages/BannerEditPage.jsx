import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  Box,
  Button,
  TextField,
  Typography,
  Paper,
  Grid,
  Switch,
  FormControlLabel,
  FormGroup,
  Select,
  MenuItem,
  InputLabel,
  FormControl,
} from '@mui/material';
import apiClient from '../api/apiClient';
import MediaSelector from '../components/MediaSelector'; // Yeni eklendi

const BannerEditPage = () => {
  const { bannerId } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(bannerId);

  const [banner, setBanner] = useState({
    baslik: '',
    hedef_url: '',
    resim_url_mobil: '',
    resim_url_desktop: '',
    konum: 'anasayfa_ust',
    sira: 0,
    aktif_mi: true,
  });

  const [loading, setLoading] = useState(false);

  // Normalde bu konumlar API'dan veya bir config dosyasından gelmeli
  const bannerKonumlari = [
    { value: 'anasayfa_ust', label: 'Anasayfa Üst Carousel' },
    { value: 'anasayfa_orta', label: 'Anasayfa Orta Bölüm' },
    { value: 'kategori_ust', label: 'Kategori Sayfası Üst' },
  ];

  useEffect(() => {
    if (isEditMode) {
      const fetchBanner = async () => {
        try {
          setLoading(true);
          const response = await apiClient.get(`/api/admin/bannerlar/${bannerId}`);
          setBanner(response.data);
        } catch (error) {
          console.error("Banner yüklenirken hata oluştu:", error);
        } finally {
          setLoading(false);
        }
      };
      fetchBanner();
    }
  }, [bannerId, isEditMode]);

  const handleChange = (event) => {
    const { name, value, type, checked } = event.target;
    setBanner((prevBanner) => ({
      ...prevBanner,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    try {
      if (isEditMode) {
        await apiClient.put(`/api/admin/bannerlar/${bannerId}`, banner);
      } else {
        await apiClient.post('/api/admin/bannerlar', banner);
      }
      navigate('/admin/bannerlar');
    } catch (error) {
      console.error("Banner kaydedilirken hata oluştu:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Paper sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        {isEditMode ? 'Bannerı Düzenle' : 'Yeni Banner Ekle'}
      </Typography>
      <Box component="form" noValidate autoComplete="off" onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          <Grid item xs={12}>
            <TextField name="baslik" label="Banner Başlığı" value={banner.baslik} onChange={handleChange} fullWidth required />
          </Grid>
          <Grid item xs={12}>
            <TextField name="hedef_url" label="Hedef URL (Tıklanınca gidilecek link)" value={banner.hedef_url} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12} sm={6}>
            <MediaSelector
              label="Mobil Görsel"
              value={banner.resim_url_mobil}
              onChange={(url) => setBanner(b => ({ ...b, resim_url_mobil: url }))}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <MediaSelector
              label="Desktop Görsel"
              value={banner.resim_url_desktop}
              onChange={(url) => setBanner(b => ({ ...b, resim_url_desktop: url }))}
            />
          </Grid>
          <Grid item xs={12} sm={6}>
            <FormControl fullWidth>
              <InputLabel>Konum</InputLabel>
              <Select name="konum" value={banner.konum} label="Konum" onChange={handleChange}>
                {bannerKonumlari.map((konum) => (
                  <MenuItem key={konum.value} value={konum.value}>{konum.label}</MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="sira" label="Sıralama" type="number" value={banner.sira} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12}>
            <FormGroup>
              <FormControlLabel
                control={<Switch name="aktif_mi" checked={banner.aktif_mi} onChange={handleChange} />}
                label="Aktif / Pasif"
              />
            </FormGroup>
          </Grid>
          <Grid item xs={12} sx={{ mt: 2, display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
            <Button variant="outlined" onClick={() => navigate('/admin/bannerlar')}>
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

export default BannerEditPage;
