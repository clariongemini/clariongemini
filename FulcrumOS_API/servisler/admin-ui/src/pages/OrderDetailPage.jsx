import React, { useState, useEffect, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import {
  Box,
  Typography,
  Paper,
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
import OrderStatusUpdateModal from '../components/OrderStatusUpdateModal';
import AddShippingModal from '../components/AddShippingModal';

const OrderDetailPage = () => {
  const { siparisId } = useParams();
  const [order, setOrder] = useState(null);
  const [auditLog, setAuditLog] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  // Modal state'leri
  const [statusModalOpen, setStatusModalOpen] = useState(false);
  const [shippingModalOpen, setShippingModalOpen] = useState(false);

  const fetchOrderDetails = useCallback(async () => {
    try {
      setLoading(true);
      const [orderResponse, logResponse] = await Promise.all([
        apiClient.get(`/api/admin/siparisler/${siparisId}`),
        apiClient.get(`/api/admin/siparisler/${siparisId}/gecmis`)
      ]);
      setOrder(orderResponse.data.veri);
      setAuditLog(logResponse.data.veri);
      setError('');
    } catch (err) {
      console.error("Sipariş detayları yüklenirken hata oluştu:", err);
      setError('Sipariş detayları yüklenemedi.');
    } finally {
      setLoading(false);
    }
  }, [siparisId]);

  useEffect(() => {
    fetchOrderDetails();
  }, [fetchOrderDetails]);

  if (loading) {
    return <CircularProgress />;
  }

  if (error) {
    return <Alert severity="error">{error}</Alert>;
  }

  if (!order) {
    return <Typography>Sipariş bulunamadı.</Typography>;
  }

  return (
    <Box>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
            <Typography variant="h4" component="h1">
                Sipariş Detayı #{order.siparis_id}
            </Typography>
            <Box>
                <Button variant="contained" onClick={() => setStatusModalOpen(true)} sx={{ mr: 2 }}>
                    Durum Güncelle
                </Button>
                <Button variant="outlined" onClick={() => setShippingModalOpen(true)}>
                    Kargo Bilgisi Ekle
                </Button>
            </Box>
        </Box>

        <Grid container spacing={3}>
            {/* Sol Sütun */}
            <Grid item xs={12} md={8}>
                <Card>
                    <CardHeader title="Sipariş Edilen Ürünler" />
                    <CardContent>
                        <List disablePadding>
                            {order.urunler.map((item) => (
                                <ListItem key={item.siparis_detay_id} divider>
                                    <ListItemText
                                        primary={item.urun_adi}
                                        secondary={`SKU: ${item.varyant_sku} | Adet: ${item.adet} | Birim Fiyat: ${item.fiyat.toFixed(2)} TL`}
                                    />
                                    <Typography variant="body2">{(item.adet * item.fiyat).toFixed(2)} TL</Typography>
                                </ListItem>
                            ))}
                        </List>
                        <Divider />
                        <Typography variant="h6" align="right" sx={{ mt: 2 }}>
                            Toplam: {order.toplam_tutar.toFixed(2)} TL
                        </Typography>
                    </CardContent>
                </Card>

                {/* Sipariş Geçmişi (Audit Log) */}
                <Card sx={{ mt: 3 }}>
                    <CardHeader title="Sipariş Geçmişi" />
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

            {/* Sağ Sütun */}
            <Grid item xs={12} md={4}>
                <Card sx={{ mb: 3 }}>
                    <CardHeader title="Müşteri Bilgileri" />
                    <CardContent>
                        {/* Bu bilgiler normalde API'den zenginleştirilerek gelmeli */}
                        <Typography>Ad Soyad: Müşteri Adı</Typography>
                        <Typography>E-posta: musteri@email.com</Typography>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader title="Teslimat Adresi" />
                    <CardContent>
                        <Typography>{order.adres.adres_satiri_1}</Typography>
                        <Typography>{order.adres.ilce} / {order.adres.il}</Typography>
                        <Typography>{order.adres.posta_kodu}</Typography>
                    </CardContent>
                </Card>
            </Grid>
        </Grid>

        <OrderStatusUpdateModal
            open={statusModalOpen}
            onClose={() => setStatusModalOpen(false)}
            order={order}
            onUpdate={fetchOrderDetails}
        />
        <AddShippingModal
            open={shippingModalOpen}
            onClose={() => setShippingModalOpen(false)}
            orderId={order.siparis_id}
            onUpdate={fetchOrderDetails}
        />
    </Box>
  );
};

export default OrderDetailPage;
