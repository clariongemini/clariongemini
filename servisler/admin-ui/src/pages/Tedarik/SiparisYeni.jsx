import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Paper, Autocomplete, Table, TableBody, TableCell, TableHead, TableRow, IconButton, Grid } from '@mui/material';
import DeleteIcon from '@mui/icons-material/Delete';
import apiClient from '../../services/apiClient';

const SiparisYeni = () => {
    const navigate = useNavigate();
    const [tedarikciler, setTedarikciler] = useState([]);
    const [depolar, setDepolar] = useState([]);
    const [seciliTedarikci, setSeciliTedarikci] = useState(null);
    const [seciliDepo, setSeciliDepo] = useState(null);
    const [urunAramaSonuclari, setUrunAramaSonuclari] = useState([]);
    const [siparisUrunleri, setSiparisUrunleri] = useState([]);

    useEffect(() => {
        apiClient.get('/admin/tedarik/tedarikciler').then(res => setTedarikciler(res.data.veri || []));
        apiClient.get('/admin/organizasyon/depolar').then(res => setDepolar(res.data.veri || []));
    }, []);

    const handleUrunArama = (event, value) => {
        if (value && value.length > 2) {
            apiClient.get(`/admin/urunler?search=${value}`).then(res => setUrunAramaSonuclari(res.data.veri || []));
        }
    };

    const handleUrunEkle = (event, urun) => {
        if (urun && !siparisUrunleri.find(u => u.varyant_id === urun.varyant_id)) {
            setSiparisUrunleri([...siparisUrunleri, { ...urun, adet: 1, maliyet_fiyati: 0 }]);
        }
    };

    const handleUrunGuncelle = (varyantId, alan, deger) => {
        setSiparisUrunleri(siparisUrunleri.map(u =>
            u.varyant_id === varyantId ? { ...u, [alan]: parseFloat(deger) || 0 } : u
        ));
    };

    const handleUrunSil = (varyantId) => {
        setSiparisUrunleri(siparisUrunleri.filter(u => u.varyant_id !== varyantId));
    };

    const handleSubmit = () => {
        const payload = {
            tedarikci_id: seciliTedarikci.tedarikci_id,
            hedef_depo_id: seciliDepo.depo_id,
            urunler: siparisUrunleri.map(u => ({ varyant_id: u.varyant_id, adet: u.adet, maliyet_fiyati: u.maliyet_fiyati }))
        };
        apiClient.post('/admin/tedarik/siparisler', payload)
            .then(() => navigate('/admin/tedarik/siparisler'))
            .catch(error => console.error("Sipariş oluşturulurken hata:", error));
    };

    const formGecerliMi = seciliTedarikci && seciliDepo && siparisUrunleri.length > 0;

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4" gutterBottom>Yeni Satın Alma Siparişi</Typography>
            <Paper sx={{ p: 2, mb: 2 }}>
                <Grid container spacing={2}>
                    <Grid item xs={12} md={6}>
                        <Autocomplete
                            options={tedarikciler}
                            getOptionLabel={(option) => option.firma_adi}
                            onChange={(e, value) => setSeciliTedarikci(value)}
                            renderInput={(params) => <TextField {...params} label="Tedarikçi Seçin" required />}
                        />
                    </Grid>
                    <Grid item xs={12} md={6}>
                        <Autocomplete
                            options={depolar}
                            getOptionLabel={(option) => option.depo_adi}
                            onChange={(e, value) => setSeciliDepo(value)}
                            renderInput={(params) => <TextField {...params} label="Hedef Depo Seçin" required />}
                        />
                    </Grid>
                </Grid>
            </Paper>
            <Paper sx={{ p: 2 }}>
                <Typography variant="h6">Ürünler</Typography>
                <Autocomplete
                    options={urunAramaSonuclari}
                    getOptionLabel={(option) => `${option.urun_adi} - SKU: ${option.sku}` || ''}
                    onInputChange={handleUrunArama}
                    onChange={handleUrunEkle}
                    renderInput={(params) => <TextField {...params} label="Ürün Ara (SKU veya İsim)" />}
                />
                <Table sx={{ mt: 2 }}>
                    <TableHead>
                        <TableRow>
                            <TableCell>Ürün Adı</TableCell>
                            <TableCell>SKU</TableCell>
                            <TableCell align="right">Adet</TableCell>
                            <TableCell align="right">Maliyet Fiyatı</TableCell>
                            <TableCell align="center">İşlem</TableCell>
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {siparisUrunleri.map((urun) => (
                            <TableRow key={urun.varyant_id}>
                                <TableCell>{urun.urun_adi}</TableCell>
                                <TableCell>{urun.sku}</TableCell>
                                <TableCell align="right">
                                    <TextField type="number" value={urun.adet} onChange={(e) => handleUrunGuncelle(urun.varyant_id, 'adet', e.target.value)} sx={{ width: 100 }} />
                                </TableCell>
                                <TableCell align="right">
                                    <TextField type="number" value={urun.maliyet_fiyati} onChange={(e) => handleUrunGuncelle(urun.varyant_id, 'maliyet_fiyati', e.target.value)} sx={{ width: 120 }} />
                                </TableCell>
                                <TableCell align="center">
                                    <IconButton onClick={() => handleUrunSil(urun.varyant_id)}><DeleteIcon /></IconButton>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </Paper>
            <Button variant="contained" sx={{ mt: 3 }} onClick={handleSubmit} disabled={!formGecerliMi}>Siparişi Oluştur</Button>
        </Box>
    );
};

export default SiparisYeni;
