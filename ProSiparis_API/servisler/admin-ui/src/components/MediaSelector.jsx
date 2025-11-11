import React, { useState } from 'react';
import { Box, Button, Typography, Modal, Paper, Avatar } from '@mui/material';
import MediaGalleryPage from '../pages/MediaGalleryPage';

const style = {
  position: 'absolute',
  top: '50%',
  left: '50%',
  transform: 'translate(-50%, -50%)',
  width: '80%',
  height: '80%',
  bgcolor: 'background.paper',
  border: '2px solid #000',
  boxShadow: 24,
  p: 4,
  overflowY: 'auto',
};

const MediaSelector = ({ value, onChange, label }) => {
  const [open, setOpen] = useState(false);

  const handleOpen = () => setOpen(true);
  const handleClose = () => setOpen(false);

  const handleMediaSelect = (media) => {
    onChange(media.url);
    handleClose();
  };

  const handleRemove = () => {
    onChange(''); // Değeri temizle
  };

  return (
    <Box>
      <Typography variant="subtitle1" gutterBottom>{label}</Typography>
      <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
        <Avatar
          src={value}
          variant="rounded"
          sx={{ width: 100, height: 100, bgcolor: 'grey.200' }}
        >
          Görsel
        </Avatar>
        <Box>
            <Button variant="outlined" onClick={handleOpen}>
                Medya Galerisi'nden Seç
            </Button>
            {value && (
                <Button variant="text" color="error" onClick={handleRemove} sx={{ ml: 1 }}>
                    Kaldır
                </Button>
            )}
        </Box>
      </Box>

      <Modal
        open={open}
        onClose={handleClose}
        aria-labelledby="media-gallery-modal-title"
      >
        <Paper sx={style}>
          <Typography id="media-gallery-modal-title" variant="h6" component="h2" sx={{ mb: 2 }}>
            Medya Seçin
          </Typography>
          <MediaGalleryPage onSelect={handleMediaSelect} />
        </Paper>
      </Modal>
    </Box>
  );
};

export default MediaSelector;
