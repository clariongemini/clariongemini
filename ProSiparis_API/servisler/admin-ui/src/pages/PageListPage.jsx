import React, { useState, useEffect } from 'react';
import { Box, Button, Typography, IconButton } from '@mui/material';
import { DataGrid, GridToolbar } from '@mui/x-data-grid';
import { Link as RouterLink } from 'react-router-dom';
import apiClient from '../api/apiClient';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

const PageListPage = () => {
  const [pages, setPages] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchPages = async () => {
      try {
        setLoading(true);
        const response = await apiClient.get('/api/admin/sayfalar');
        setPages(response.data || []);
      } catch (error) {
        console.error("Sayfalar yüklenirken hata oluştu:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchPages();
  }, []);

  const handleDelete = async (id) => {
    if (window.confirm('Bu sayfayı silmek istediğinizden emin misiniz?')) {
      try {
        await apiClient.delete(`/api/admin/sayfalar/${id}`);
        setPages(pages.filter((page) => page.id !== id));
      } catch (error) {
        console.error("Sayfa silinirken hata oluştu:", error);
      }
    }
  };

  const columns = [
    { field: 'id', headerName: 'ID', width: 90 },
    { field: 'baslik', headerName: 'Sayfa Başlığı', flex: 1, minWidth: 250 },
    { field: 'slug', headerName: 'URL (Slug)', flex: 1, minWidth: 200 },
    {
      field: 'aktif_mi',
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
            to={`/admin/sayfalar/${params.id}/duzenle`}
            color="primary"
          >
            <EditIcon />
          </IconButton>
          <IconButton onClick={() => handleDelete(params.id)} color="error">
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
          Sayfa Yönetimi
        </Typography>
        <Button
          variant="contained"
          component={RouterLink}
          to="/admin/sayfalar/yeni"
        >
          Yeni Sayfa Ekle
        </Button>
      </Box>
      <Box sx={{ height: 600, width: '100%' }}>
        <DataGrid
          rows={pages}
          columns={columns}
          loading={loading}
          components={{ Toolbar: GridToolbar }}
          pageSizeOptions={[10, 25, 50]}
          initialState={{
            pagination: { paginationModel: { pageSize: 10 } },
          }}
        />
      </Box>
    </Box>
  );
};

export default PageListPage;
