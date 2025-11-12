import React, { useState, useEffect } from 'react';
import { Box, Button, Typography } from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import { useNavigate } from 'react-router-dom';
import apiClient from '../../services/apiClient'; // Varsayımsal API istemcisi

const TedarikciListele = () => {
    const [tedarikciler, setTedarikciler] = useState([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        apiClient.get('/admin/tedarik/tedarikciler')
            .then(response => {
                setTedarikciler(response.data.veri);
                setLoading(false);
            })
            .catch(error => {
                console.error("Tedarikçiler yüklenirken hata oluştu:", error);
                setLoading(false);
            });
    }, []);

    const columns = [
        { field: 'tedarikci_id', headerName: 'ID', width: 90 },
        { field: 'firma_adi', headerName: 'Firma Adı', width: 250 },
        { field: 'yetkili_kisi', headerName: 'Yetkili Kişi', width: 200 },
        { field: 'eposta', headerName: 'E-posta', width: 200 },
        { field: 'telefon', headerName: 'Telefon', width: 150 },
        {
            field: 'actions',
            headerName: 'İşlemler',
            sortable: false,
            width: 150,
            renderCell: (params) => (
                <Button
                    variant="contained"
                    size="small"
                    onClick={() => navigate(`/admin/tedarikciler/duzenle/${params.id}`)}
                >
                    Düzenle
                </Button>
            ),
        },
    ];

    return (
        <Box sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                <Typography variant="h4">Tedarikçiler</Typography>
                <Button variant="contained" onClick={() => navigate('/admin/tedarikciler/yeni')}>
                    Yeni Tedarikçi Ekle
                </Button>
            </Box>
            <Box sx={{ height: 600, width: '100%' }}>
                <DataGrid
                    rows={tedarikciler}
                    columns={columns}
                    loading={loading}
                    getRowId={(row) => row.tedarikci_id}
                />
            </Box>
        </Box>
    );
};

export default TedarikciListele;
