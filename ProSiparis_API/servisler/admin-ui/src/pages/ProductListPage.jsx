import React, { useState, useEffect } from 'react';
import { Box, Button, Typography, IconButton } from '@mui/material';
import { DataGrid, GridToolbar } from '@mui/x-data-grid';
import { Link as RouterLink } from 'react-router-dom';
import apiClient from '../api/apiClient';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

const ProductListPage = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchProducts = async () => {
      try {
        setLoading(true);
        const response = await apiClient.get('/api/admin/urunler');
        setProducts(response.data || []);
      } catch (error) {
        console.error("Ürünler yüklenirken hata oluştu:", error);
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, []);

  const handleDelete = async (id) => {
    if (window.confirm('Bu ürünü silmek istediğinizden emin misiniz?')) {
      try {
        await apiClient.delete(`/api/admin/urunler/${id}`);
        setProducts(products.filter((product) => product.id !== id));
      } catch (error) {
        console.error("Ürün silinirken hata oluştu:", error);
      }
    }
  };

  const columns = [
    { field: 'id', headerName: 'ID', width: 90 },
    {
      field: 'ad',
      headerName: 'Ürün Adı',
      flex: 1,
      minWidth: 200,
    },
    {
      field: 'kategori',
      headerName: 'Kategori',
      flex: 1,
      minWidth: 150,
      valueGetter: (params) => params.row.kategori?.ad || 'N/A',
    },
    {
      field: 'fiyat',
      headerName: 'Fiyat',
      type: 'number',
      width: 130,
    },
    {
      field: 'durum',
      headerName: 'Durum',
      width: 120,
      renderCell: (params) => (
        <Typography color={params.value ? 'success.main' : 'error.main'}>
          {params.value ? 'Aktif' : 'Pasif'}
        </Typography>
      ),
    },
    {
      field: 'eylemler',
      headerName: 'Eylemler',
      sortable: false,
      filterable: false,
      disableColumnMenu: true,
      width: 150,
      renderCell: (params) => (
        <>
          <IconButton
            component={RouterLink}
            to={`/admin/urunler/${params.id}/duzenle`}
            color="primary"
          >
            <EditIcon />
          </IconButton>
          <IconButton
            onClick={() => handleDelete(params.id)}
            color="error"
          >
            <DeleteIcon />
          </IconButton>
        </>
      ),
    },
  ];

  return (
    <Box sx={{ width: '100%' }}>
      <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
        <Typography variant="h4" component="h1">
          Ürün Yönetimi
        </Typography>
        <Button
          variant="contained"
          component={RouterLink}
          to="/admin/urunler/yeni"
        >
          Yeni Ürün Ekle
        </Button>
      </Box>
      <Box sx={{ height: 600, width: '100%' }}>
        <DataGrid
          rows={products}
          columns={columns}
          loading={loading}
          components={{ Toolbar: GridToolbar }}
          pageSizeOptions={[10, 25, 50]}
          initialState={{
            pagination: {
              paginationModel: {
                pageSize: 10,
              },
            },
          }}
          sx={{
            // Mobil cihazlar için scroll bar görünürlüğü
            '& .MuiDataGrid-virtualScroller': {
              overflowX: 'auto',
            },
          }}
        />
      </Box>
    </Box>
  );
};

export default ProductListPage;
