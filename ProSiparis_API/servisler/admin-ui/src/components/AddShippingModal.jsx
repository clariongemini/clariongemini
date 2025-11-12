import React, { useState } from 'react';
import {
  Modal,
  Box,
  Typography,
  Button,
  TextField,
  CircularProgress,
} from '@mui/material';
import apiClient from '../api/apiClient';

const style = {
  position: 'absolute',
  top: '50%',
  left: '50%',
  transform: 'translate(-50%, -50%)',
  width: 400,
  bgcolor: 'background.paper',
  boxShadow: 24,
  p: 4,
};

const AddShippingModal = ({ open, onClose, orderId, onUpdate }) => {
  const [shippingInfo, setShippingInfo] = useState({
    kargo_tasiyici: '',
    takip_no: '',
  });
  const [loading, setLoading] = useState(false);

  const handleChange = (event) => {
    const { name, value } = event.target;
    setShippingInfo((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async () => {
    try {
      setLoading(true);
      await apiClient.post(`/api/admin/siparisler/${orderId}/kargo`, shippingInfo);
      onUpdate(); // Detay sayfasını yenile
      onClose();
    } catch (error) {
      console.error('Kargo bilgisi eklenirken hata:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal open={open} onClose={onClose}>
      <Box sx={style}>
        <Typography variant="h6" component="h2" sx={{ mb: 2 }}>
          Kargo Bilgisi Ekle
        </Typography>
        <TextField
          name="kargo_tasiyici"
          label="Kargo Şirketi"
          value={shippingInfo.kargo_tasiyici}
          onChange={handleChange}
          fullWidth
          sx={{ mb: 2 }}
        />
        <TextField
          name="takip_no"
          label="Kargo Takip Numarası"
          value={shippingInfo.takip_no}
          onChange={handleChange}
          fullWidth
        />
        <Box sx={{ mt: 3, display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
          <Button variant="text" onClick={onClose}>
            İptal
          </Button>
          <Button variant="contained" onClick={handleSubmit} disabled={loading}>
            {loading ? <CircularProgress size={24} /> : 'Ekle'}
          </Button>
        </Box>
      </Box>
    </Modal>
  );
};

export default AddShippingModal;
