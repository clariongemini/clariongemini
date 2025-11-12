import React, { useState } from 'react';
import { Box, Button, TextField, Typography, Paper } from '@mui/material';
import apiClient from '../../services/apiClient'; // Bu istemcinin mock'lanacağını varsayıyoruz

const ProductCreatePage = ({ onSave }) => {
    const [urunAdi, setUrunAdi] = useState('');

    const handleSubmit = async (event) => {
        event.preventDefault();
        try {
            const response = await apiClient.post('/api/admin/urunler', { urun_adi: urunAdi });
            if (onSave) {
                onSave(response.data);
            }
        } catch (error) {
            console.error("Ürün kaydedilirken hata:", error);
        }
    };

    return (
        <Paper sx={{ p: 2 }}>
            <Typography variant="h6">Yeni Ürün</Typography>
            <Box component="form" onSubmit={handleSubmit}>
                <TextField
                    label="Ürün Adı"
                    value={urunAdi}
                    onChange={(e) => setUrunAdi(e.target.value)}
                    fullWidth
                    margin="normal"
                />
                <Button type="submit" variant="contained">Kaydet</Button>
            </Box>
        </Paper>
    );
};

export default ProductCreatePage;
