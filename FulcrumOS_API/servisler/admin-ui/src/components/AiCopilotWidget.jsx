// FulcrumOS v10.4: AI Co-Pilot Widget Component
import React, { useState, useEffect } from 'react';
import { Card, CardContent, Typography, Box, CircularProgress, Alert } from '@mui/material';
import LightbulbIcon from '@mui/icons-material/Lightbulb';
import axios from 'axios';

const AiCopilotWidget = () => {
  const [suggestion, setSuggestion] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    axios.get('/api/admin/ai-co-pilot/oneriler')
      .then(response => {
        if (response.data.basarili && response.data.oneri) {
          // "Her şey yolunda" mesajını boş durum olarak değerlendir
          if (response.data.oneri.includes("yolunda görünüyor")) {
            setSuggestion(null);
          } else {
            setSuggestion(response.data.oneri);
          }
        } else {
          setSuggestion(null); // API başarılı ama öneri yok
        }
        setLoading(false);
      })
      .catch(err => {
        console.error("AI Co-Pilot önerisi alınırken hata:", err);
        setError("Öneri yüklenirken bir hata oluştu.");
        setLoading(false);
      });
  }, []);

  if (loading) {
    return (
      <Card sx={{ mb: 3 }}>
        <CardContent>
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            <CircularProgress size={24} sx={{ mr: 2 }} />
            <Typography variant="h6">AI Co-Pilot verileri analiz ediyor...</Typography>
          </Box>
        </CardContent>
      </Card>
    );
  }

  if (error) {
    return <Alert severity="error" sx={{ mb: 3 }}>{error}</Alert>;
  }

  if (!suggestion) {
    return (
        <Alert severity="info" sx={{ mb: 3 }}>
            Şu an için proaktif bir öneri bulunmuyor. Sistem verileri analiz etmeye devam ediyor.
        </Alert>
    );
  }

  return (
    <Alert severity="warning" icon={<LightbulbIcon />} sx={{ mb: 3 }}>
        <Typography variant="h6" component="div">
            Proaktif AI Önerisi
        </Typography>
        <Typography sx={{ mt: 1 }}>
            {suggestion}
        </Typography>
    </Alert>
  );
};

export default AiCopilotWidget;
