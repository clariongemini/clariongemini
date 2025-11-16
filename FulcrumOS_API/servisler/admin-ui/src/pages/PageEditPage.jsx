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
} from '@mui/material';
import apiClient from '../api/apiClient';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css'; // Quill'in "snow" teması için CSS

const PageEditPage = () => {
  const { sayfaId } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(sayfaId);

  const [page, setPage] = useState({
    baslik: '',
    icerik: '',
    aktif_mi: true,
    // SEO Fields (v5.2)
    meta_baslik: '',
    meta_aciklama: '',
    slug: '',
  });

  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (isEditMode) {
      const fetchPage = async () => {
        try {
          setLoading(true);
          const response = await apiClient.get(`/api/admin/sayfalar/${sayfaId}`);
          const fetchedPage = response.data;
          setPage(fetchedPage);
          setContent(fetchedPage.icerik || '');
        } catch (error) {
          console.error("Sayfa yüklenirken hata oluştu:", error);
        } finally {
          setLoading(false);
        }
      };
      fetchPage();
    }
  }, [sayfaId, isEditMode]);

  const handleChange = (event) => {
    const { name, value, type, checked } = event.target;
    setPage((prevPage) => ({
      ...prevPage,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleContentChange = (value) => {
    setContent(value);
    setPage((prevPage) => ({
      ...prevPage,
      icerik: value,
    }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    try {
      if (isEditMode) {
        await apiClient.put(`/api/admin/sayfalar/${sayfaId}`, page);
      } else {
        await apiClient.post('/api/admin/sayfalar', page);
      }
      navigate('/admin/sayfalar');
    } catch (error) {
      console.error("Sayfa kaydedilirken hata oluştu:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Paper sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        {isEditMode ? 'Sayfayı Düzenle' : 'Yeni Sayfa Ekle'}
      </Typography>
      <Box component="form" noValidate autoComplete="off" onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          <Grid item xs={12}>
            <TextField
              name="baslik"
              label="Sayfa Başlığı"
              value={page.baslik}
              onChange={handleChange}
              fullWidth
              required
            />
          </Grid>

          <Grid item xs={12}>
            <Typography sx={{ mb: 1, color: 'text.secondary' }}>Sayfa İçeriği</Typography>
            <ReactQuill
              theme="snow"
              value={content}
              onChange={handleContentChange}
              style={{ height: '250px', marginBottom: '50px' }}
            />
          </Grid>

          {/* SEO Bilgileri */}
          <Grid item xs={12}>
            <Typography variant="h6">SEO Bilgileri</Typography>
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="slug" label="URL (Slug)" value={page.slug} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="meta_baslik" label="Meta Başlık" value={page.meta_baslik} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12}>
             <TextField name="meta_aciklama" label="Meta Açıklama" value={page.meta_aciklama} onChange={handleChange} fullWidth multiline rows={2}/>
          </Grid>

          <Grid item xs={12}>
            <FormGroup>
              <FormControlLabel
                control={<Switch name="aktif_mi" checked={page.aktif_mi} onChange={handleChange} />}
                label="Aktif / Pasif"
              />
            </FormGroup>
          </Grid>

          <Grid item xs={12} sx={{ mt: 2, display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
            <Button variant="outlined" onClick={() => navigate('/admin/sayfalar')}>
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

export default PageEditPage;
