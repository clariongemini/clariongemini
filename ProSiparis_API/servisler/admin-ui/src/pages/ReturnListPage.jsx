import React, { useState, useEffect } from 'react';
import { Box, Typography, IconButton, Chip } from '@mui/material';
import { DataGrid, GridToolbar } from '@mui/x-data-grid';
import { useNavigate } from 'react-router-dom';
import apiClient from '../api/apiClient';
import VisibilityIcon from '@mui/icons-material/Visibility';

// Durum renklendirmesi için bir harita
const durumRenkleri = {
  'Talep Alındı': 'primary',
  'Onaylandı': 'secondary',
  'Ürünler Geldi': 'info',
  'İncelemede': 'warning',
  'Odeme Yapıldı': 'success',
  'Reddedildi': 'error',
};

const ReturnListPage = () => {
  const [returns, setReturns] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchReturns = async () => {
      try {
        setLoading(true);
        const response = await apiClient.get('/api/admin/iadeler');
        setReturns(response.data.veri || []);
      } catch (error) {
        console.error("İadeler yüklenirken hata oluştu:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchReturns();
  }, []);

  const columns = [
    { field: 'iade_id', headerName: 'ID', width: 90 },
    { field: 'siparis_id', headerName: 'İlişkili Sipariş ID', width: 150 },
    { field: 'ad_soyad', headerName: 'Müşteri', flex: 1, minWidth: 180 },
    {
      field: 'talep_tarihi',
      headerName: 'Talep Tarihi',
      width: 160,
      renderCell: (params) => new Date(params.value).toLocaleString('tr-TR'),
    },
    {
      field: 'durum',
      headerName: 'Durum',
      width: 150,
      renderCell: (params) => (
        <Chip label={params.value} color={durumRenkleri[params.value] || 'default'} size="small" />
      ),
    },
    {
      field: 'eylemler',
      headerName: 'Eylemler',
      sortable: false,
      filterable: false,
      disableColumnMenu: true,
      width: 100,
      renderCell: (params) => (
        <IconButton
          onClick={() => navigate(`/admin/iadeler/${params.row.iade_id}`)}
          color="primary"
        >
          <VisibilityIcon />
        </IconButton>
      ),
    },
  ];

  return (
    <Box sx={{ width: '100%' }}>
      <Typography variant="h4" component="h1" sx={{ mb: 2 }}>
        İade Yönetimi
      </Typography>
      <Box sx={{ height: 650, width: '100%' }}>
        <DataGrid
          rows={returns}
          columns={columns}
          loading={loading}
          getRowId={(row) => row.iade_id}
          components={{ Toolbar: GridToolbar }}
          pageSizeOptions={[10, 25, 50, 100]}
          initialState={{
            pagination: { paginationModel: { pageSize: 25 } },
            sorting: {
              sortModel: [{ field: 'talep_tarihi', sort: 'desc' }],
            },
          }}
        />
      </Box>
    </Box>
  );
};

export default ReturnListPage;
