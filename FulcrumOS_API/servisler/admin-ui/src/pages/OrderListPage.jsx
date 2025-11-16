import React, { useState, useEffect } from 'react';
import { Box, Typography, IconButton, Chip } from '@mui/material';
import { DataGrid, GridToolbar } from '@mui/x-data-grid';
import { useNavigate } from 'react-router-dom';
import apiClient from '../api/apiClient';
import VisibilityIcon from '@mui/icons-material/Visibility';

// Durum renklendirmesi için bir harita
const durumRenkleri = {
  'yeni': 'primary',
  'onaylandi': 'secondary',
  'hazirlaniyor': 'info',
  'kargoya_verildi': 'warning',
  'teslim_edildi': 'success',
  'iptal_edildi': 'error',
};

const OrderListPage = () => {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    const fetchOrders = async () => {
      try {
        setLoading(true);
        const response = await apiClient.get('/api/admin/siparisler');
        setOrders(response.data.veri || []);
      } catch (error) {
        console.error("Siparişler yüklenirken hata oluştu:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchOrders();
  }, []);

  const columns = [
    { field: 'siparis_id', headerName: 'ID', width: 90 },
    { field: 'ad_soyad', headerName: 'Müşteri', flex: 1, minWidth: 180 },
    {
      field: 'siparis_tarihi',
      headerName: 'Tarih',
      width: 160,
      renderCell: (params) => new Date(params.value).toLocaleString('tr-TR'),
    },
    {
      field: 'toplam_tutar',
      headerName: 'Tutar',
      type: 'number',
      width: 130,
      renderCell: (params) => `${params.value.toFixed(2)} TL`,
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
          onClick={() => navigate(`/admin/siparisler/${params.row.siparis_id}`)}
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
        Sipariş Yönetimi
      </Typography>
      <Box sx={{ height: 650, width: '100%' }}>
        <DataGrid
          rows={orders}
          columns={columns}
          loading={loading}
          getRowId={(row) => row.siparis_id}
          components={{ Toolbar: GridToolbar }}
          pageSizeOptions={[10, 25, 50, 100]}
          initialState={{
            pagination: { paginationModel: { pageSize: 25 } },
            sorting: {
              sortModel: [{ field: 'siparis_tarihi', sort: 'desc' }],
            },
          }}
        />
      </Box>
    </Box>
  );
};

export default OrderListPage;
