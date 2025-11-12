import React from 'react';
import { Card, CardContent, Typography } from '@mui/material';

const KPICard = ({ title, value }) => {
  return (
    <Card>
      <CardContent>
        <Typography color="text.secondary" gutterBottom>
          {title}
        </Typography>
        <Typography variant="h5" component="div">
          {value}
        </Typography>
      </CardContent>
    </Card>
  );
};

export default KPICard;
