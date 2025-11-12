import React, { useState } from 'react';
import {
  Modal,
  Box,
  Typography,
  Button,
  Select,
  MenuItem,
  FormControl,
  InputLabel,
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

const siparisDurumlari = [
  'yeni',
  'onaylandi',
  'hazirlaniyor',
  'kargoya_verildi',
  'teslim_edildi',
  'iptal_edildi',
];

const OrderStatusUpdateModal = ({ open, onClose, order, onUpdate }) => {
  const [newStatus, setNewStatus] = useState(order.durum);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    try {
      setLoading(true);
      await apiClient.put(`/api/admin/siparisler/${order.siparis_id}/durum`, {
        yeni_durum: newStatus,
      });
      onUpdate(); // Detay sayfasını yenile
      onClose();
    } catch (error) {
      console.error('Sipariş durumu güncellenirken hata:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal open={open} onClose={onClose}>
      <Box sx={style}>
        <Typography variant="h6" component="h2" sx={{ mb: 2 }}>
          Sipariş Durumunu Güncelle
        </Typography>
        <FormControl fullWidth>
          <InputLabel>Yeni Durum</InputLabel>
          <Select
            value={newStatus}
            label="Yeni Durum"
            onChange={(e) => setNewStatus(e.target.value)}
          >
            {siparisDurumlari.map((durum) => (
              <MenuItem key={durum} value={durum}>
                {durum.charAt(0).toUpperCase() + durum.slice(1)}
              </MenuItem>
            ))}
          </Select>
        </FormControl>
        <Box sx={{ mt: 3, display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
          <Button variant="text" onClick={onClose}>
            İptal
          </Button>
          <Button variant="contained" onClick={handleSubmit} disabled={loading}>
            {loading ? <CircularProgress size={24} /> : 'Kaydet'}
          </Button>
        </Box>
      </Box>
    </Modal>
  );
};

export default OrderStatusUpdateModal;
