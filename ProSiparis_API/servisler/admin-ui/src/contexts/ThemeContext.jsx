import React, { createContext, useState, useMemo, useContext, useEffect } from 'react';
import { createTheme } from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';

const ThemeContext = createContext();

export const ThemeModeProvider = ({ children }) => {
  const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
  const [mode, setMode] = useState(() => {
    const storedMode = localStorage.getItem('themeMode');
    return storedMode || 'system';
  });

  const theme = useMemo(() => {
    let activeMode;
    if (mode === 'system') {
      activeMode = prefersDarkMode ? 'dark' : 'light';
    } else {
      activeMode = mode;
    }
    return createTheme({
      palette: {
        mode: activeMode,
      },
    });
  }, [mode, prefersDarkMode]);

  const changeMode = (newMode) => {
    localStorage.setItem('themeMode', newMode);
    setMode(newMode);
  };

  return (
    <ThemeContext.Provider value={{ mode, changeMode, theme }}>
      {children}
    </ThemeContext.Provider>
  );
};

export const useThemeMode = () => {
    return useContext(ThemeContext);
};
