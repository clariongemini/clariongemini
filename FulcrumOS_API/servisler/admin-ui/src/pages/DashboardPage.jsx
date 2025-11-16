import React, { useEffect, useState } from 'react';
import { Grid, Card, CardContent, Typography } from '@mui/material';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import apiClient from '../api/apiClient';
import { useAuth } from '../contexts/AuthContext'; // Yetki kontrolü için
import AiCopilotWidget from '../components/AiCopilotWidget'; // Yeni AI Widget'ı

const DashboardPage = () => {
  const { user } = useAuth();
  const [kpiData, setKpiData] = useState(null);
  const [chartData, setChartData] = useState([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const kpiResponse = await apiClient.get('/admin/dashboard/kpi-ozet');
        setKpiData(kpiResponse.data.veri);

        const chartResponse = await apiClient.get('/admin/dashboard/satis-grafigi');
        setChartData(chartResponse.data.veri);
      } catch (error) {
        console.error("Dashboard verileri çekilirken hata oluştu:", error);
      }
    };

    fetchData();
  }, []);

  return (
    <div>
      {/* v10.4: AI Co-Pilot Widget */}
      {user && user.permissions && user.permissions.includes('ai_copilot_goruntule') && <AiCopilotWidget />}

      <Typography variant="h4" gutterBottom>
        Dashboard
      </Typography>

      {/* KPI Kartları */}
      <Grid container spacing={3} sx={{ mb: 4 }}>
        {kpiData && Object.entries(kpiData).map(([key, value]) => (
          <Grid item xs={12} sm={6} md={3} key={key}>
            <Card>
              <CardContent>
                <Typography sx={{ fontSize: 14 }} color="text.secondary" gutterBottom>
                  {key.replace(/_/g, ' ').toUpperCase()}
                </Typography>
                <Typography variant="h5" component="div">
                  {value}
                </Typography>
              </CardContent>
            </Card>
          </Grid>
        ))}
      </Grid>

      {/* Satış Grafiği */}
      <Typography variant="h5" gutterBottom>
        Son 30 Günlük Satış Grafiği
      </Typography>
      <Card>
        <CardContent>
          <ResponsiveContainer width="100%" height={400}>
            <LineChart data={chartData}>
              <CartesianGrid strokeDasharray="3 3" />
              <XAxis dataKey="tarih" />
              <YAxis />
              <Tooltip />
              <Legend />
              <Line type="monotone" dataKey="toplam_satis" stroke="#8884d8" name="Toplam Satış (TL)" />
            </LineChart>
          </ResponsiveContainer>
        </CardContent>
      </Card>
    </div>
  );
};

export default DashboardPage;
