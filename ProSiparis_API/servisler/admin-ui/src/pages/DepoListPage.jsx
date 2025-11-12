import React, { useState, useEffect } from 'react';
import { Box, Button, Typography, IconButton } from '@mui/material';
import { DataGrid, GridToolbar } from '@mui/x-data-grid';
import { Link as RouterLink } from 'react-router-dom';
import apiClient from '../api/apiClient';
import EditIcon from '@mui/icons-material/Edit';
import DeleteIcon from '@mui/icons-material/Delete';

const DepoListPage = () => {
  const [depolar, setDepolar] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDepolar = async () => {
      try {
        setLoading(true);
        // Backend'de oluşturduğumuz yeni admin endpoint'ini çağırıyoruz
        const response = await apiClient.get('/api/admin/organizasyon/depolar');
        setDepolar(response.data.veri || []);
      } catch (error) {
        console.error("Depolar yüklenirken hata oluştu:", error);
      } finally {
        setLoading(false);
      }
    };
    fetchDepolar();
  }, []);

  const handleDelete = async (id) => {
    if (window.confirm('Bu depoyu silmek istediğinizden emin misiniz?')) {
      try {
        await apiClient.delete(`/api/admin/organizasyon/depolar/${id}`);
        setDepolar(depolar.filter((depo) => depo.depo_id !== id));
      } catch (error) {
        console.error("Depo silinirken hata oluştu:", error);
      }
    }
  };

  const columns = [
    { field: 'depo_id', headerName: 'ID', width: 90 },
    { field: 'depo_adi', headerName: 'Depo Adı', flex: 1, minWidth: 200 },
    { field: 'depo_kodu', headerName: 'Depo Kodu', flex: 1, minWidth: 150 },
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
            to={`/admin/depolar/${params.row.depo_id}/duzenle`}
            color="primary"
          >
            <EditIcon />
          </IconButton>
          <IconButton onClick={() => handleDelete(params.row.depo_id)} color="error">
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
          Depo Yönetimi
        </Typography>
        <Button
          variant="contained"
          component={RouterLink}
          to="/admin/depolar/yeni"
        >
          Yeni Depo Ekle
        </Button>
      </Box>
      <Box sx={{ height: 600, width: '100%' }}>
        <DataGrid
          rows={depolar}
          columns={columns}
          loading={loading}
          getRowId={(row) => row.depo_id} // Satır ID'sini belirtiyoruz
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

export default DepoListPage;
