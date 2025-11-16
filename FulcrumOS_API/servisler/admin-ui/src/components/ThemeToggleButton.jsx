import React from 'react';
import { ToggleButton, ToggleButtonGroup } from '@mui/material';
import { useThemeMode } from '../contexts/ThemeContext';
import LightModeIcon from '@mui/icons-material/LightMode';
import DarkModeIcon from '@mui/icons-material/DarkMode';
import SettingsSystemDaydreamIcon from '@mui/icons-material/SettingsSystemDaydream';

const ThemeToggleButton = () => {
  const { mode, changeMode } = useThemeMode();

  const handleModeChange = (event, newMode) => {
    if (newMode !== null) {
      changeMode(newMode);
    }
  };

  return (
    <ToggleButtonGroup
      value={mode}
      exclusive
      onChange={handleModeChange}
      aria-label="tema seçimi"
    >
      <ToggleButton value="light" aria-label="açık tema">
        <LightModeIcon />
      </ToggleButton>
      <ToggleButton value="dark" aria-label="koyu tema">
        <DarkModeIcon />
      </ToggleButton>
      <ToggleButton value="system" aria-label="sistem teması">
        <SettingsSystemDaydreamIcon />
      </ToggleButton>
    </ToggleButtonGroup>
  );
};

export default ThemeToggleButton;
