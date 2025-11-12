import React, { useState, useEffect, useContext } from 'react';
import { useParams } from 'react-router-dom';
import { Box, Button, Typography, Paper, Tabs, Tab, Dialog, DialogTitle, DialogContent, DialogActions, TextField } from '@mui/material';
import apiClient from '../../services/apiClient';
import { AuthContext } from '../../context/AuthContext'; // Yetki kontrolü için

const SiparisDetay = () => {
    const { poId } = useParams();
    const { kullanici } = useContext(AuthContext); // Varsayımsal Auth context'i
    const [siparis, setSiparis] = useState(null);
    const [gecmis, setGecmis] = useState([]);
    const [tabIndex, setTabIndex] = useState(0);
    const [modalOpen, setModalOpen] = useState(false);
    const [teslimAlinacaklar, setTeslimAlinacaklar] = useState({});

    useEffect(() => {
        apiClient.get(`/admin/tedarik/siparisler/${poId}`).then(res => setSiparis(res.data.veri));
        apiClient.get(`/admin/tedarik/siparisler/${poId}/gecmis`).then(res => setGecmis(res.data.veri));
    }, [poId]);

    const handleTeslimAl = () => {
        const payload = {
            teslim_edilen_urunler: Object.entries(teslimAlinacaklar).map(([varyant_id, adet]) => ({ varyant_id, teslim_alinan_adet: adet }))
        };
        apiClient.post(`/admin/tedarik/siparisler/${poId}/teslim-al`, payload)
            .then(() => {
                setModalOpen(false);
                // Sayfayı yenilemek için verileri tekrar çek
                apiClient.get(`/admin/tedarik/siparisler/${poId}`).then(res => setSiparis(res.data.veri));
            })
            .catch(error => console.error("Teslim alma işlemi başarısız:", error));
    };

    // ... (Diğer handle fonksiyonları)

    if (!siparis) return <Typography>Yükleniyor...</Typography>;

    const kullaniciYetkiliMi = kullanici.yetkiler.includes('tedarik_teslim_al');

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4">Sipariş Detayı #{siparis.po_id}</Typography>
            {kullaniciYetkiliMi && siparis.durum !== 'tamamlandi' && (
                <Button variant="contained" onClick={() => setModalOpen(true)}>Ürünleri Teslim Al</Button>
            )}

            <Tabs value={tabIndex} onChange={(e, newValue) => setTabIndex(newValue)}>
                <Tab label="Sipariş Detayları" />
                <Tab label="Geçmiş" />
            </Tabs>

            {/* Tab panelleri: Detaylar ve Geçmiş */}

            <Dialog open={modalOpen} onClose={() => setModalOpen(false)}>
                <DialogTitle>Ürün Teslim Alma</DialogTitle>
                <DialogContent>
                    {/* Teslim alma formu: Her ürün için sipariş edilen, teslim alınan, kalan ve teslim alınacak adet inputları */}
                    {/* Örnek bir ürün satırı için: */}
                    {/* <TextField label="Teslim Alınacak Adet" type="number" error={hataVarMi} helperText={hataMesaji} /> */}
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setModalOpen(false)}>İptal</Button>
                    <Button onClick={handleTeslimAl}>Teslim Al</Button>
                </DialogActions>
            </Dialog>
        </Box>
    );
};

export default SiparisDetay;
