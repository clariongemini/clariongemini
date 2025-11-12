import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Box, Button, TextField, Typography, Paper, Autocomplete, Table, TableBody, TableCell, TableHead, TableRow, IconButton } from '@mui/material';
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
        apiClient.get('/admin/tedarik/tedarikciler').then(res => setTedarikciler(res.data.veri));
        apiClient.get('/admin/organizasyon/depolar').then(res => setDepolar(res.data.veri));
    }, []);

    const handleUrunArama = (event, value) => {
        if (value.length > 2) {
            apiClient.get(`/api/admin/urunler?search=${value}`).then(res => setUrunAramaSonuclari(res.data.veri));
        }
    };

    const handleUrunEkle = (event, urun) => {
        if (urun && !siparisUrunleri.find(u => u.varyant_id === urun.varyant_id)) {
            setSiparisUrunleri([...siparisUrunleri, { ...urun, adet: 1, maliyet_fiyati: 0 }]);
        }
    };

    // ... (Ürün miktarını/fiyatını güncelleme ve silme handle fonksiyonları)

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

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4" gutterBottom>Yeni Satın Alma Siparişi</Typography>
            <Paper sx={{ p: 2, mb: 2 }}>
                {/* Tedarikçi ve Depo Seçimi Autocomplete'leri */}
            </Paper>
            <Paper sx={{ p: 2 }}>
                <Typography variant="h6">Ürünler</Typography>
                <Autocomplete
                    options={urunAramaSonuclari}
                    getOptionLabel={(option) => `${option.urun_adi} - ${option.sku}`}
                    onInputChange={handleUrunArama}
                    onChange={handleUrunEkle}
                    renderInput={(params) => <TextField {...params} label="Ürün Ara (SKU veya İsim)" />}
                />
                {/* Sipariş ürünlerinin listelendiği Table */}
            </Paper>
            <Button variant="contained" sx={{ mt: 3 }} onClick={handleSubmit}>Siparişi Oluştur</Button>
        </Box>
    );
};

export default SiparisYeni;
