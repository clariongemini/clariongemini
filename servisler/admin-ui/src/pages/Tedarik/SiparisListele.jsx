import React, { useState, useEffect } from 'react';
import { Box, Button, Typography, Chip } from '@mui/material';
import { DataGrid } from '@mui/x-data-grid';
import { useNavigate } from 'react-router-dom';
import apiClient from '../../services/apiClient';

const durumRenkleri = {
    taslak: 'default',
    gonderildi: 'warning',
    kismen_teslim_alindi: 'info',
    tamamlandi: 'success',
};

const SiparisListele = () => {
    const [siparisler, setSiparisler] = useState([]);
    const [loading, setLoading] = useState(true);
    const navigate = useNavigate();

    useEffect(() => {
        apiClient.get('/admin/tedarik/siparisler')
            .then(response => {
                setSiparisler(response.data.veri);
                setLoading(false);
            })
            .catch(error => {
                console.error("Satın alma siparişleri yüklenirken hata:", error);
                setLoading(false);
            });
    }, []);

    const columns = [
        { field: 'po_id', headerName: 'PO ID', width: 90 },
        { field: 'firma_adi', headerName: 'Tedarikçi', width: 250 },
        { field: 'hedef_depo_id', headerName: 'Hedef Depo ID', width: 130 },
        {
            field: 'durum',
            headerName: 'Durum',
            width: 180,
            renderCell: (params) => (
                <Chip label={params.value} color={durumRenkleri[params.value] || 'default'} />
            ),
        },
        { field: 'olusturulma_tarihi', headerName: 'Oluşturulma Tarihi', width: 200 },
        {
            field: 'actions',
            headerName: 'İşlemler',
            sortable: false,
            width: 150,
            renderCell: (params) => (
                <Button
                    variant="contained"
                    size="small"
                    onClick={() => navigate(`/admin/tedarik/siparisler/${params.id}`)}
                >
                    Detayları Gör
                </Button>
            ),
        },
    ];

    return (
        <Box sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', justifyContent: 'space-between', mb: 2 }}>
                <Typography variant="h4">Satın Alma Siparişleri</Typography>
                <Button variant="contained" onClick={() => navigate('/admin/tedarik/siparisler/yeni')}>
                    Yeni Sipariş Oluştur
                </Button>
            </Box>
            <Box sx={{ height: 600, width: '100%' }}>
                <DataGrid
                    rows={siparisler}
                    columns={columns}
                    loading={loading}
                    getRowId={(row) => row.po_id}
                />
            </Box>
        </Box>
    );
};

export default SiparisListele;
