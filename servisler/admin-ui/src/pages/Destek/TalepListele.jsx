import React, { useState, useEffect } from 'react';
import { Box, Button, Typography, Chip, Tabs, Tab } from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import { useNavigate } from 'react-router-dom';
import apiClient from '../../services/apiClient';

const durumRenkleri = {
    acik: 'warning',
    cevaplandi: 'info',
    kapandi: 'success',
};

const TalepListele = () => {
    const [talepler, setTalepler] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filtre, setFiltre] = useState('acik');
    const navigate = useNavigate();

    useEffect(() => {
        setLoading(true);
        apiClient.get(`/admin/destek-talepleri?durum=${filtre}`)
            .then(response => {
                setTalepler(response.data.veri);
                setLoading(false);
            })
            .catch(error => {
                console.error("Destek talepleri yüklenirken hata oluştu:", error);
                setLoading(false);
            });
    }, [filtre]);

    const handleFiltreChange = (event, yeniFiltre) => {
        setFiltre(yeniFiltre);
    };

    const columns = [
        { field: 'talep_id', headerName: 'Talep ID', width: 90 },
        { field: 'konu', headerName: 'Konu', flex: 1 },
        { field: 'musteri_adi', headerName: 'Müşteri', width: 200 },
        {
            field: 'durum',
            headerName: 'Durum',
            width: 150,
            renderCell: (params) => (
                <Chip label={params.value} color={durumRenkleri[params.value] || 'default'} size="small" />
            ),
        },
        { field: 'son_guncelleme_tarihi', headerName: 'Son Güncelleme', width: 180 },
        {
            field: 'actions',
            headerName: 'İşlemler',
            sortable: false,
            width: 120,
            renderCell: (params) => (
                <Button
                    variant="contained"
                    size="small"
                    onClick={() => navigate(`/admin/destek-talepleri/${params.id}`)}
                >
                    Cevapla
                </Button>
            ),
        },
    ];

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4" gutterBottom>Destek Talepleri</Typography>
            <Box sx={{ borderBottom: 1, borderColor: 'divider', mb: 2 }}>
                <Tabs value={filtre} onChange={handleFiltreChange}>
                    <Tab label="Açık" value="acik" />
                    <Tab label="Cevaplandı" value="cevaplandi" />
                    <Tab label="Kapandı" value="kapandi" />
                </Tabs>
            </Box>
            <Box sx={{ height: 650, width: '100%' }}>
                <DataGrid
                    rows={talepler}
                    columns={columns}
                    loading={loading}
                    getRowId={(row) => row.talep_id}
                />
            </Box>
        </Box>
    );
};

export default TalepListele;
