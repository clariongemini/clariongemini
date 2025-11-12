import React, { useState, useEffect, useContext } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { Box, Button, Typography, Paper, Tabs, Tab, Dialog, DialogTitle, DialogContent, DialogActions, TextField, Table, TableBody, TableCell, TableHead, TableRow, Grid } from '@mui/material';
import apiClient from '../../services/apiClient';
import { AuthContext } from '../../context/AuthContext'; // Yetki kontrolü için

const SiparisDetay = () => {
    const { poId } = useParams();
    const navigate = useNavigate();
    const { kullanici } = useContext(AuthContext);
    const [siparis, setSiparis] = useState(null);
    const [gecmis, setGecmis] = useState([]);
    const [tabIndex, setTabIndex] = useState(0);
    const [modalOpen, setModalOpen] = useState(false);
    const [teslimAlinacaklar, setTeslimAlinacaklar] = useState({});

    const fetchData = () => {
        apiClient.get(`/admin/tedarik/siparisler/${poId}`).then(res => setSiparis(res.data.veri));
        apiClient.get(`/admin/tedarik/siparisler/${poId}/gecmis`).then(res => setGecmis(res.data.veri));
    };

    useEffect(() => {
        fetchData();
    }, [poId]);

    const handleModalInputChange = (varyantId, value, kalanAdet) => {
        const adet = Math.max(0, Math.min(kalanAdet, Number(value)));
        setTeslimAlinacaklar(prev => ({ ...prev, [varyantId]: adet }));
    };

    const handleTeslimAl = () => {
        const payload = {
            teslim_edilen_urunler: Object.entries(teslimAlinacaklar)
                .filter(([, adet]) => adet > 0)
                .map(([varyant_id, adet]) => ({ varyant_id: parseInt(varyant_id), teslim_alinan_adet: adet }))
        };

        if (payload.teslim_edilen_urunler.length === 0) return;

        apiClient.post(`/admin/tedarik/siparisler/${poId}/teslim-al`, payload)
            .then(() => {
                setModalOpen(false);
                setTeslimAlinacaklar({});
                fetchData();
            })
            .catch(error => console.error("Teslim alma işlemi başarısız:", error));
    };

    if (!siparis) return <Typography>Yükleniyor...</Typography>;

    const kullaniciYetkiliMi = kullanici.yetkiler.includes('tedarik_teslim_al');
    const teslimAlinacakUrunVarMi = Object.values(teslimAlinacaklar).some(adet => adet > 0);

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4">Sipariş Detayı #{siparis.po_id}</Typography>
            {kullaniciYetkiliMi && siparis.durum !== 'tamamlandi' && (
                <Button variant="contained" onClick={() => setModalOpen(true)} sx={{ my: 2 }}>Ürünleri Teslim Al</Button>
            )}
            {/* ... Tabs and other components */}

            <Dialog open={modalOpen} onClose={() => setModalOpen(false)} fullWidth maxWidth="md">
                <DialogTitle>Ürün Teslim Alma</DialogTitle>
                <DialogContent>
                    <Table>
                        <TableHead>
                            <TableRow>
                                <TableCell>Ürün</TableCell>
                                <TableCell align="right">Sipariş Edilen</TableCell>
                                <TableCell align="right">Teslim Alınan</TableCell>
                                <TableCell align="right">Kalan</TableCell>
                                <TableCell align="center">Teslim Alınacak</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {siparis.urunler.map(urun => {
                                const kalan = urun.siparis_edilen_adet - urun.teslim_alinan_adet;
                                if (kalan <= 0) return null;
                                return (
                                    <TableRow key={urun.varyant_id}>
                                        <TableCell>{urun.urun_adi || `Varyant ID: ${urun.varyant_id}`}</TableCell>
                                        <TableCell align="right">{urun.siparis_edilen_adet}</TableCell>
                                        <TableCell align="right">{urun.teslim_alinan_adet}</TableCell>
                                        <TableCell align="right">{kalan}</TableCell>
                                        <TableCell align="center">
                                            <TextField
                                                type="number"
                                                value={teslimAlinacaklar[urun.varyant_id] || ''}
                                                onChange={(e) => handleModalInputChange(urun.varyant_id, e.target.value, kalan)}
                                                InputProps={{ inputProps: { max: kalan, min: 0 } }}
                                                sx={{ width: 100 }}
                                            />
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setModalOpen(false)}>İptal</Button>
                    <Button onClick={handleTeslimAl} variant="contained" disabled={!teslimAlinacakUrunVarMi}>Teslim Al</Button>
                </DialogActions>
            </Dialog>
        </Box>
    );
};

export default SiparisDetay;
