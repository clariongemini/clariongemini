import React, { useState } from 'react';
import { Button, TextField, Dialog, DialogActions, DialogContent, DialogTitle } from '@mui/material';

const TeslimAlModal = ({ open, onClose, kalanAdet, onSave }) => {
    const [teslimAlinacakAdet, setTeslimAlinacakAdet] = useState('');

    const adetGecerliMi = Number(teslimAlinacakAdet) > 0 && Number(teslimAlinacakAdet) <= kalanAdet;

    const handleAdetChange = (e) => {
        setTeslimAlinacakAdet(e.target.value);
    };

    const handleSave = () => {
        if (adetGecerliMi) {
            onSave(Number(teslimAlinacakAdet));
        }
    };

    return (
        <Dialog open={open} onClose={onClose}>
            <DialogTitle>Ürün Teslim Al</DialogTitle>
            <DialogContent>
                <TextField
                    label="Teslim Alınacak Adet"
                    type="number"
                    value={teslimAlinacakAdet}
                    onChange={handleAdetChange}
                    helperText={`Kalan Adet: ${kalanAdet}`}
                    error={Number(teslimAlinacakAdet) > kalanAdet}
                />
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>İptal</Button>
                <Button onClick={handleSave} variant="contained" disabled={!adetGecerliMi}>
                    Kaydet
                </Button>
            </DialogActions>
        </Dialog>
    );
};

export default TeslimAlModal;
