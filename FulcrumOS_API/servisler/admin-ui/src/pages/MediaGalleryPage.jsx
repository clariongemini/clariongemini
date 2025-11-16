import React, { useState, useEffect, useCallback } from 'react';
import {
  Box,
  Button,
  Typography,
  ImageList,
  ImageListItem,
  ImageListItemBar,
  IconButton,
  CircularProgress,
  Alert,
} from '@mui/material';
import apiClient from '../api/apiClient';
import DeleteIcon from '@mui/icons-material/Delete';
import UploadFileIcon from '@mui/icons-material/UploadFile';
import CheckCircleIcon from '@mui/icons-material/CheckCircle';

const MediaGalleryPage = ({ onSelect }) => {
  const [media, setMedia] = useState([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  const fetchMedia = useCallback(async () => {
    try {
      setLoading(true);
      const response = await apiClient.get('/api/admin/medya');
      setMedia(response.data.veri || []);
      setError('');
    } catch (err) {
      console.error("Medya yüklenirken hata oluştu:", err);
      setError('Medya kütüphanesi yüklenemedi.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchMedia();
  }, [fetchMedia]);

  const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('dosya', file);

    try {
      setUploading(true);
      setError('');
      await apiClient.post('/api/admin/medya/yukle', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });
      await fetchMedia(); // Galeriyi yenile
    } catch (err) {
      console.error("Dosya yüklenirken hata:", err);
      setError(err.response?.data?.mesaj || 'Dosya yüklenemedi.');
    } finally {
      setUploading(false);
    }
  };

  const handleDelete = async (dosyaId) => {
      if (window.confirm('Bu dosyayı kalıcı olarak silmek istediğinizden emin misiniz?')) {
          try {
              setError('');
              await apiClient.delete(`/api/admin/medya/${dosyaId}`);
              await fetchMedia(); // Galeriyi yenile
          } catch (err) {
              console.error("Dosya silinirken hata:", err);
              setError(err.response?.data?.mesaj || 'Dosya silinemedi.');
          }
      }
  };

  return (
    <Box sx={{ width: '100%' }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h4" component="h1">
          Medya Kütüphanesi
        </Typography>
        <Button
          variant="contained"
          component="label"
          startIcon={uploading ? <CircularProgress size={20} color="inherit" /> : <UploadFileIcon />}
          disabled={uploading}
        >
          Yeni Dosya Yükle
          <input type="file" hidden onChange={handleFileUpload} />
        </Button>
      </Box>

      {error && <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert>}

      {loading ? (
        <CircularProgress />
      ) : (
        <ImageList variant="masonry" cols={5} gap={8}>
          {media.map((item) => (
            <ImageListItem key={item.dosya_id}>
              <img
                src={`${item.url}?w=248&fit=crop&auto=format`}
                srcSet={`${item.url}?w=248&fit=crop&auto=format&dpr=2 2x`}
                alt={item.dosya_adi}
                loading="lazy"
              />
              <ImageListItemBar
                title={item.dosya_adi}
                subtitle={<span>Boyut: {(item.boyut / 1024).toFixed(2)} KB</span>}
                actionIcon={
                  <>
                    {onSelect && (
                      <IconButton
                        sx={{ color: 'rgba(255, 255, 255, 0.9)', backgroundColor: 'success.main', mr: 1 }}
                        aria-label={`select ${item.dosya_adi}`}
                        onClick={() => onSelect(item)}
                      >
                        <CheckCircleIcon />
                      </IconButton>
                    )}
                    <IconButton
                      sx={{ color: 'rgba(255, 255, 255, 0.7)' }}
                      aria-label={`delete ${item.dosya_adi}`}
                      onClick={() => handleDelete(item.dosya_id)}
                    >
                      <DeleteIcon />
                    </IconButton>
                  </>
                }
              />
            </ImageListItem>
          ))}
        </ImageList>
      )}
    </Box>
  );
};

export default MediaGalleryPage;
