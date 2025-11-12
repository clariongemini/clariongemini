import React, { useState, useEffect } from 'react';
import { Box, Button, Typography, IconButton, Avatar } from '@mui/material';
import { DataGrid, GridToolbar } from '@mui/x-data-grid';
import { Link as RouterLink } from 'react-router-dom';
import apiClient from '../api/apiClient';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

const BannerListPage = () => {
  const [banners, setBanners] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchBanners = async () => {
      try {
        setLoading(true);
        const response = await apiClient.get('/api/admin/bannerlar');
        setBanners(response.data || []);
      } catch (error) {
        console.error("Bannerlar yüklenirken hata oluştu:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchBanners();
  }, []);

  const handleDelete = async (id) => {
    if (window.confirm('Bu bannerı silmek istediğinizden emin misiniz?')) {
      try {
        await apiClient.delete(`/api/admin/bannerlar/${id}`);
        setBanners(banners.filter((banner) => banner.id !== id));
      } catch (error) {
        console.error("Banner silinirken hata oluştu:", error);
      }
    }
  };

  const columns = [
    { field: 'id', headerName: 'ID', width: 90 },
    {
      field: 'resim_url_mobil',
      headerName: 'Önizleme',
      width: 100,
      renderCell: (params) => <Avatar src={params.value} variant="rounded" sx={{ width: 80, height: 45 }} />
    },
    { field: 'baslik', headerName: 'Banner Başlığı', flex: 1, minWidth: 200 },
    { field: 'hedef_url', headerName: 'Hedef URL', flex: 1, minWidth: 200 },
    { field: 'konum', headerName: 'Konum', width: 150 },
    { field: 'sira', headerName: 'Sıra', width: 100 },
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
            to={`/admin/bannerlar/${params.id}/duzenle`}
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
          Banner Yönetimi
        </Typography>
        <Button
          variant="contained"
          component={RouterLink}
          to="/admin/bannerlar/yeni"
        >
          Yeni Banner Ekle
        </Button>
      </Box>
      <Box sx={{ height: 600, width: '100%' }}>
        <DataGrid
          rows={banners}
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

export default BannerListPage;
