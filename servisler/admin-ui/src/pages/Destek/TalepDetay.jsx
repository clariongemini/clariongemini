import React, { useState, useEffect, useContext } from 'react';
import { useParams } from 'react-router-dom';
import { Box, TextField, Button, Typography, Paper, List, ListItem, ListItemText, Divider, Chip } from '@mui/material';
import SendIcon from '@mui/icons-material/Send';
import apiClient from '../../services/apiClient';
import { AuthContext } from '../../context/AuthContext';

const TalepDetay = () => {
    const { talepId } = useParams();
    const { kullanici } = useContext(AuthContext); // Varsayımsal Auth context'i
    const [talep, setTalep] = useState(null);
    const [mesajlar, setMesajlar] = useState([]);
    const [yeniMesaj, setYeniMesaj] = useState('');
    const [loading, setLoading] = useState(true);

    const fetchData = () => {
        apiClient.get(`/admin/destek-talepleri/${talepId}`)
            .then(response => {
                setTalep(response.data.veri);
                setMesajlar(response.data.veri.mesajlar);
                setLoading(false);
            })
            .catch(error => console.error("Talep detayı yüklenirken hata:", error));
    };

    useEffect(() => {
        fetchData();
    }, [talepId]);

    const handleMesajGonder = () => {
        if (yeniMesaj.trim() === '') return;
        apiClient.post(`/admin/destek-talepleri/${talepId}/mesaj`, { mesaj: yeniMesaj })
            .then(() => {
                setYeniMesaj('');
                fetchData(); // Mesaj listesini yenile
            })
            .catch(error => console.error("Mesaj gönderilirken hata:", error));
    };

    if (loading) {
        return <Typography>Yükleniyor...</Typography>;
    }

    return (
        <Box sx={{ p: 3 }}>
            <Typography variant="h4" gutterBottom>Destek Talebi #{talep.talep_id}: {talep.konu}</Typography>
            <Typography variant="subtitle1" gutterBottom>Müşteri: {talep.musteri_adi} <Chip label={talep.durum} size="small" /></Typography>

            <Paper sx={{ height: '60vh', display: 'flex', flexDirection: 'column', mt: 2 }}>
                <List sx={{ flexGrow: 1, overflow: 'auto', p: 2 }}>
                    {mesajlar.map((mesaj, index) => (
                        <ListItem key={index} sx={{ display: 'flex', justifyContent: mesaj.gonderen_id === kullanici.id ? 'flex-end' : 'flex-start' }}>
                            <Paper variant="outlined" sx={{ p: 1, bgcolor: mesaj.gonderen_id === kullanici.id ? 'primary.light' : 'grey.200', maxWidth: '70%' }}>
                                <ListItemText
                                    primary={mesaj.mesaj}
                                    secondary={new Date(mesaj.gonderilme_tarihi).toLocaleString()}
                                />
                            </Paper>
                        </ListItem>
                    ))}
                </List>
                <Divider />
                <Box sx={{ p: 2, display: 'flex', alignItems: 'center' }}>
                    <TextField
                        fullWidth
                        variant="outlined"
                        placeholder="Cevabınızı yazın..."
                        value={yeniMesaj}
                        onChange={(e) => setYeniMesaj(e.target.value)}
                        onKeyPress={(e) => e.key === 'Enter' && handleMesajGonder()}
                    />
                    <Button
                        variant="contained"
                        endIcon={<SendIcon />}
                        onClick={handleMesajGonder}
                        sx={{ ml: 1 }}
                    >
                        Gönder
                    </Button>
                </Box>
            </Paper>
        </Box>
    );
};

export default TalepDetay;
