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
  RadioGroup,
  Radio,
} from '@mui/material';
import apiClient from '../api/apiClient';
import MediaSelector from '../components/MediaSelector'; // Yeni eklendi

const ProductEditPage = () => {
  const { urunId } = useParams();
  const navigate = useNavigate();
  const isEditMode = Boolean(urunId);
  const [product, setProduct] = useState({
    ad: '',
    aciklama: '',
    ana_gorsel_url: '', // Yeni alan
    kategori_id: '',
    fiyat: 0,
    durum: true,
    // SEO Fields (v5.2)
    meta_baslik: '',
    meta_aciklama: '',
    slug: '',
    // Merchant Fields (v5.2)
    gtin: '',
    mpn: '',
    marka: '',
    // WMS Fields (v4.0)
    takip_yontemi: 'adet',
  });
  const [loading, setLoading] = useState(false);

  // Varsayılan kategoriler, normalde API'dan gelmeli
  const [categories, setCategories] = useState([]);

  useEffect(() => {
    // Kategori listesini çek
    const fetchCategories = async () => {
        try {
            // Gerçek endpoint'i buraya ekleyin, şimdilik mock data
            // const response = await apiClient.get('/api/admin/kategoriler');
            // setCategories(response.data);
            setCategories([
              { id: 1, ad: 'Elektronik' },
              { id: 2, ad: 'Giyim' },
              { id: 3, ad: 'Ev & Yaşam' },
            ]);
        } catch (error) {
            console.error("Kategoriler yüklenirken hata oluştu:", error);
        }
    };

    fetchCategories();

    if (isEditMode) {
      const fetchProduct = async () => {
        try {
          setLoading(true);
          const response = await apiClient.get(`/api/admin/urunler/${urunId}`);
          setProduct(response.data);
        } catch (error) {
          console.error("Ürün yüklenirken hata oluştu:", error);
        } finally {
          setLoading(false);
        }
      };
      fetchProduct();
    }
  }, [urunId, isEditMode]);

  const handleChange = (event) => {
    const { name, value, type, checked } = event.target;
    setProduct((prevProduct) => ({
      ...prevProduct,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    try {
      if (isEditMode) {
        await apiClient.put(`/api/admin/urunler/${urunId}`, product);
      } else {
        await apiClient.post('/api/admin/urunler', product);
      }
      navigate('/admin/urunler');
    } catch (error) {
      console.error("Ürün kaydedilirken hata oluştu:", error);
      // Kullanıcıya bir hata mesajı gösterilebilir
    } finally {
      setLoading(false);
    }
  };

  return (
    <Paper sx={{ p: 3 }}>
      <Typography variant="h4" gutterBottom>
        {isEditMode ? 'Ürünü Düzenle' : 'Yeni Ürün Ekle'}
      </Typography>
      <Box component="form" noValidate autoComplete="off" onSubmit={handleSubmit}>
        <Grid container spacing={3}>
          {/* Temel Bilgiler */}
          <Grid item xs={12}>
            <TextField
              name="ad"
              label="Ürün Adı"
              value={product.ad}
              onChange={handleChange}
              fullWidth
              required
            />
          </Grid>
          <Grid item xs={12}>
            <TextField
              name="aciklama"
              label="Açıklama"
              value={product.aciklama}
              onChange={handleChange}
              multiline
              rows={4}
              fullWidth
            />
          </Grid>
          <Grid item xs={12}>
            <MediaSelector
              label="Ana Ürün Görseli"
              value={product.ana_gorsel_url}
              onChange={(url) => setProduct(p => ({ ...p, ana_gorsel_url: url }))}
            />
          </Grid>
           <Grid item xs={12} sm={6}>
            <FormControl fullWidth>
              <InputLabel>Kategori</InputLabel>
              <Select
                name="kategori_id"
                value={product.kategori_id}
                label="Kategori"
                onChange={handleChange}
              >
                {categories.map((cat) => (
                  <MenuItem key={cat.id} value={cat.id}>{cat.ad}</MenuItem>
                ))}
              </Select>
            </FormControl>
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField
              name="fiyat"
              label="Fiyat"
              type="number"
              value={product.fiyat}
              onChange={handleChange}
              fullWidth
            />
          </Grid>
           <Grid item xs={12}>
              <FormControl component="fieldset">
                <Typography component="legend">Stok Takip Yöntemi</Typography>
                <RadioGroup row name="takip_yontemi" value={product.takip_yontemi} onChange={handleChange}>
                  <FormControlLabel value="adet" control={<Radio />} label="Adet Bazlı" />
                  <FormControlLabel value="seri_no" control={<Radio />} label="Seri Numarası Bazlı" />
                </RadioGroup>
              </FormControl>
            </Grid>

          {/* SEO Bilgileri */}
          <Grid item xs={12}>
            <Typography variant="h6">SEO Bilgileri</Typography>
          </Grid>
          <Grid item xs={12} sm={6}>
            <TextField name="slug" label="URL (Slug)" value={product.slug} onChange={handleChange} fullWidth />
          </Grid>
           <Grid item xs={12} sm={6}>
            <TextField name="meta_baslik" label="Meta Başlık" value={product.meta_baslik} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12}>
             <TextField name="meta_aciklama" label="Meta Açıklama" value={product.meta_aciklama} onChange={handleChange} fullWidth multiline rows={2}/>
          </Grid>

          {/* Merchant Bilgileri */}
          <Grid item xs={12}>
            <Typography variant="h6">Merchant Bilgileri (Google, vb.)</Typography>
          </Grid>
          <Grid item xs={12} sm={4}>
            <TextField name="marka" label="Marka" value={product.marka} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12} sm={4}>
            <TextField name="gtin" label="GTIN (Barkod)" value={product.gtin} onChange={handleChange} fullWidth />
          </Grid>
          <Grid item xs={12} sm={4}>
            <TextField name="mpn" label="MPN (Üretici Parça No)" value={product.mpn} onChange={handleChange} fullWidth />
          </Grid>

          <Grid item xs={12}>
            <FormGroup>
              <FormControlLabel
                control={<Switch name="durum" checked={product.durum} onChange={handleChange} />}
                label="Aktif / Pasif"
              />
            </FormGroup>
          </Grid>
          <Grid item xs={12} sx={{ mt: 2, display: 'flex', justifyContent: 'flex-end', gap: 2 }}>
            <Button variant="outlined" onClick={() => navigate('/admin/urunler')}>
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

export default ProductEditPage;
