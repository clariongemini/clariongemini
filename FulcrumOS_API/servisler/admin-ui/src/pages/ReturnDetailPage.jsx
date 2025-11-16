import React, { useState, useEffect, useCallback } from 'react';
import { useParams, Link as RouterLink } from 'react-router-dom';
import {
  Box,
  Typography,
  Grid,
  CircularProgress,
  Alert,
  Card,
  CardContent,
  CardHeader,
  List,
  ListItem,
  ListItemText,
  Divider,
  Button,
} from '@mui/material';
import apiClient from '../api/apiClient';
import ReturnStatusUpdateModal from '../components/ReturnStatusUpdateModal';

const ReturnDetailPage = () => {
  const { iadeId } = useParams();
  const [returnData, setReturnData] = useState(null);
  const [auditLog, setAuditLog] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const [statusModalOpen, setStatusModalOpen] = useState(false);

  const fetchReturnDetails = useCallback(async () => {
    try {
      setLoading(true);
      const [returnResponse, logResponse] = await Promise.all([
        apiClient.get(`/api/admin/iadeler/${iadeId}`),
        apiClient.get(`/api/admin/iadeler/${iadeId}/gecmis`)
      ]);
      setReturnData(returnResponse.data.veri);
      setAuditLog(logResponse.data.veri);
      setError('');
    } catch (err) {
      console.error("İade detayları yüklenirken hata oluştu:", err);
      setError('İade detayları yüklenemedi.');
    } finally {
      setLoading(false);
    }
  }, [iadeId]);

  useEffect(() => {
    fetchReturnDetails();
  }, [fetchReturnDetails]);

  if (loading) return <CircularProgress />;
  if (error) return <Alert severity="error">{error}</Alert>;
  if (!returnData) return <Typography>İade talebi bulunamadı.</Typography>;

  return (
    <Box>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
            <Typography variant="h4" component="h1">
                İade Detayı #{returnData.iade_id}
            </Typography>
            <Button variant="contained" onClick={() => setStatusModalOpen(true)}>
                Durum Güncelle
            </Button>
        </Box>

        <Grid container spacing={3}>
            <Grid item xs={12} md={8}>
                <Card>
                    <CardHeader title="İade Edilen Ürünler" />
                    <CardContent>
                        <List disablePadding>
                            {returnData.urunler.map((item) => (
                                <ListItem key={item.iade_urun_id} divider>
                                    <ListItemText
                                        primary={`Ürün ID: ${item.varyant_id}`}
                                        secondary={`Adet: ${item.adet} | Durum: ${item.durum}`}
                                    />
                                </ListItem>
                            ))}
                        </List>
                    </CardContent>
                </Card>

                <Card sx={{ mt: 3 }}>
                    <CardHeader title="İade Geçmişi" />
                    <CardContent>
                        <List dense>
                            {auditLog.map((log) => (
                                <ListItem key={log.log_id} divider>
                                    <ListItemText
                                        primary={log.aciklama}
                                        secondary={`İşlemi Yapan: ${log.ad_soyad || 'Sistem'} | Tarih: ${new Date(log.tarih).toLocaleString('tr-TR')}`}
                                    />
                                </ListItem>
                            ))}
                        </List>
                    </CardContent>
                </Card>
            </Grid>
            <Grid item xs={12} md={4}>
                <Card>
                    <CardHeader title="Talep Bilgileri" />
                    <CardContent>
                        <Typography>
                            İlişkili Sipariş:
                            <Button component={RouterLink} to={`/admin/siparisler/${returnData.siparis_id}`}>
                                #{returnData.siparis_id}
                            </Button>
                        </Typography>
                        <Typography>Müşteri ID: {returnData.kullanici_id}</Typography>
                        <Typography>Talep Tarihi: {new Date(returnData.talep_tarihi).toLocaleString('tr-TR')}</Typography>
                        <Divider sx={{ my: 2 }} />
                        <Typography variant="h6">Neden:</Typography>
                        <Typography paragraph>{returnData.neden || 'Belirtilmemiş'}</Typography>
                    </CardContent>
                </Card>
            </Grid>
        </Grid>

        <ReturnStatusUpdateModal open={statusModalOpen} onClose={() => setStatusModalOpen(false)} returnData={returnData} onUpdate={fetchReturnDetails} />
    </Box>
  );
};

export default ReturnDetailPage;
