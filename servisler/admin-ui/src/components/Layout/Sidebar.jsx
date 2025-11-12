import React from 'react';
import { Drawer, List, ListItemButton, ListItemIcon, ListItemText, Toolbar, Box, Typography } from '@mui/material';
import DashboardIcon from '@mui/icons-material/Dashboard';
// Diğer menü ikonları...

const drawerWidth = 240;

const Sidebar = () => {
  return (
    <Drawer
      variant="permanent"
      sx={{
        width: drawerWidth,
        flexShrink: 0,
        [`& .MuiDrawer-paper`]: { width: drawerWidth, boxSizing: 'border-box' },
      }}
    >
      <Toolbar />
      <Box sx={{ overflow: 'auto' }}>
        <List>
          {/* Varsayımsal menü öğeleri */}
          <ListItemButton>
            <ListItemIcon><DashboardIcon /></ListItemIcon>
            <ListItemText primary="Dashboard" />
          </ListItemButton>
          {/* Diğer menü öğeleri buraya gelir... */}
        </List>
      </Box>
      <Box sx={{ flexGrow: 1 }} /> {/* Bu boşluk, footer'ı aşağıya iter */}
      <Box sx={{ p: 2, textAlign: 'center' }}>
        <Typography variant="caption" display="block" gutterBottom>
          Platform: FulcrumOS v8.1
        </Typography>
        <Typography variant="caption" display="block">
          Design & Architecture by Ulaş Kaşıkcı
        </Typography>
      </Box>
    </Drawer>
  );
};

export default Sidebar;
