import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Paper } from '@mui/material';
import apiClient from '../../services/apiClient';

const TedarikciForm = () => {
    const { id } = useParams();
    const navigate = useNavigate();
    const [tedarikci, setTedarikci] = useState({
        firma_adi: '',
        yetkili_kisi: '',
        eposta: '',
        telefon: ''
    });
    const isEditMode = Boolean(id);

    useEffect(() => {
        if (isEditMode) {
            // Not: Bu endpoint'in backend'de var olduğu varsayılıyor.
            apiClient.get(`/admin/tedarik/tedarikciler/${id}`)
                .then(response => setTedarikci(response.data.veri))
                .catch(error => console.error("Tedarikçi yüklenirken hata:", error));
        }
    }, [id, isEditMode]);

    const handleChange = (event) => {
        const { name, value } = event.target;
        setTedarikci(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (event) => {
        event.preventDefault();
        const request = isEditMode
            ? apiClient.put(`/admin/tedarik/tedarikciler/${id}`, tedarikci)
            : apiClient.post('/admin/tedarik/tedarikciler', tedarikci);

        request
            .then(() => navigate('/admin/tedarikciler'))
            .catch(error => console.error("Tedarikçi kaydedilirken hata:", error));
    };

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4" gutterBottom>
                {isEditMode ? 'Tedarikçi Düzenle' : 'Yeni Tedarikçi'}
            </Typography>
            <Paper sx={{ p: 2 }}>
                <Box component="form" onSubmit={handleSubmit} noValidate sx={{ mt: 1 }}>
                    <TextField
                        margin="normal"
                        required
                        fullWidth
                        id="firma_adi"
                        label="Firma Adı"
                        name="firma_adi"
                        value={tedarikci.firma_adi}
                        onChange={handleChange}
                    />
                    <TextField
                        margin="normal"
                        fullWidth
                        name="yetkili_kisi"
                        label="Yetkili Kişi"
                        id="yetkili_kisi"
                        value={tedarikci.yetkili_kisi}
                        onChange={handleChange}
                    />
                    <TextField
                        margin="normal"
                        fullWidth
                        name="eposta"
                        label="E-posta"
                        id="eposta"
                        value={tedarikci.eposta}
                        onChange={handleChange}
                    />
                    <TextField
                        margin="normal"
                        fullWidth
                        name="telefon"
                        label="Telefon"
                        id="telefon"
                        value={tedarikci.telefon}
                        onChange={handleChange}
                    />
                    <Button
                        type="submit"
                        fullWidth
                        variant="contained"
                        sx={{ mt: 3, mb: 2 }}
                    >
                        Kaydet
                    </Button>
                </Box>
            </Paper>
        </Box>
    );
};

export default TedarikciForm;
