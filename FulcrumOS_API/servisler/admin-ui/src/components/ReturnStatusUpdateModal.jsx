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

// Bu durumlar, backend'deki iş akışına göre genişletilebilir
const iadeDurumlari = [
  'Talep Alındı',
  'Onaylandı',
  'Reddedildi',
  'Ürünler Geldi',
  'İncelemede',
  'Odeme Yapıldı',
];

const ReturnStatusUpdateModal = ({ open, onClose, returnData, onUpdate }) => {
  const [newStatus, setNewStatus] = useState(returnData.durum);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async () => {
    try {
      setLoading(true);
      await apiClient.put(`/api/admin/iadeler/${returnData.iade_id}/durum`, {
        yeni_durum: newStatus,
      });
      onUpdate(); // Detay sayfasını yenile
      onClose();
    } catch (error) {
      console.error('İade durumu güncellenirken hata:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal open={open} onClose={onClose}>
      <Box sx={style}>
        <Typography variant="h6" component="h2" sx={{ mb: 2 }}>
          İade Durumunu Güncelle
        </Typography>
        <FormControl fullWidth>
          <InputLabel>Yeni Durum</InputLabel>
          <Select
            value={newStatus}
            label="Yeni Durum"
            onChange={(e) => setNewStatus(e.target.value)}
          >
            {iadeDurumlari.map((durum) => (
              <MenuItem key={durum} value={durum}>
                {durum}
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

export default ReturnStatusUpdateModal;
