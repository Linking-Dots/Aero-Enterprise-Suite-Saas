import React, { useEffect, useState } from 'react';
import { 
  Drawer, 
  Typography, 
  Divider, 
  Box,
  IconButton,
  Switch,
  Chip,
  FormControl,
  RadioGroup,
  FormControlLabel,
  Radio
} from '@mui/material';
import {
  Palette as PaletteIcon,
  Close as CloseIcon,
  DarkMode as DarkModeIcon,
  LightMode as LightModeIcon,
  Check as CheckIcon,
  Circle as CircleIcon,
  Brush as BrushIcon,
  ColorLens as ColorLensIcon,
  Wallpaper as WallpaperIcon,
  TextFields as TextFieldsIcon,
  AutoAwesome as AutoAwesomeIcon
} from '@mui/icons-material';
import { useTheme } from "@mui/material/styles";
import { Button, Card, CardBody } from "@heroui/react";
import { THEME_COLORS, applyThemeToRoot } from '@/utils/themeUtils.js';

const ThemeSettingDrawer = ({ 
  themeDrawerOpen, 
  themeColor, 
  toggleThemeDrawer, 
  toggleThemeColor, 
  darkMode, 
  toggleDarkMode 
}) => {
  const theme = useTheme();
  
  // Local state for background and font options
  const [selectedBackground, setSelectedBackground] = useState('solid');
  const [selectedFont, setSelectedFont] = useState('primary');

  // Glassmorphism-inspired background pattern options
  const backgroundOptions = [
    {
      id: 'pattern-glass-1',
      name: 'Abstract Blobs',
      description: 'Colorful abstract blobs with glass overlay',
      preview: 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=400&q=80',
    },
    {
      id: 'pattern-glass-2',
      name: 'Soft Mesh',
      description: 'Soft mesh gradient with glass effect',
      preview: 'https://www.transparenttextures.com/patterns/cubes.png',
    },
    {
      id: 'pattern-glass-3',
      name: 'Vibrant Waves',
      description: 'Vibrant abstract waves',
      preview: 'https://images.unsplash.com/photo-1465101046530-73398c7f28ca?auto=format&fit=crop&w=400&q=80',
    },
    {
      id: 'pattern-glass-4',
      name: 'Colorful Mesh',
      description: 'Colorful glassy mesh gradient',
      preview: 'https://www.transparenttextures.com/patterns/diamond-upholstery.png',
    },
    {
      id: 'pattern-glass-5',
      name: 'Aurora Glass',
      description: 'Aurora glass effect',
      preview: 'https://images.unsplash.com/photo-1502086223501-7ea6ecd79368?auto=format&fit=crop&w=400&q=80',
    },
  ];

  // Font family options
  const fontOptions = [
    { id: 'primary', name: 'Inter', description: 'Modern sans-serif (Default)', family: 'var(--font-primary)' },
    { id: 'secondary', name: 'Fredoka', description: 'Friendly rounded font', family: 'var(--font-secondary)' },
    { id: 'mono', name: 'JetBrains Mono', description: 'Developer monospace', family: 'var(--font-mono)' },
    { id: 'serif', name: 'Playfair Display', description: 'Elegant serif font', family: 'var(--font-serif)' }
  ];
  
  // Apply theme to document root when theme changes
  useEffect(() => {
    applyThemeToRoot(themeColor, darkMode);
  }, [themeColor, darkMode]);

  // Apply background pattern
  useEffect(() => {
    document.documentElement.setAttribute('data-background', selectedBackground);
  }, [selectedBackground]);

  // Apply font family
  useEffect(() => {
    const fontOption = fontOptions.find(f => f.id === selectedFont);
    if (fontOption) {
      document.documentElement.style.setProperty('--font-current', fontOption.family);
    }
  }, [selectedFont]);

  // Load saved preferences on mount
  useEffect(() => {
    const savedBackground = localStorage.getItem('aero-hr-background') || 'solid';
    const savedFont = localStorage.getItem('aero-hr-font') || 'primary';
    setSelectedBackground(savedBackground);
    setSelectedFont(savedFont);
  }, []);

  // Save preferences when changed
  useEffect(() => {
    localStorage.setItem('aero-hr-background', selectedBackground);
  }, [selectedBackground]);

  useEffect(() => {
    localStorage.setItem('aero-hr-font', selectedFont);
  }, [selectedFont]);
  
  const getCurrentTheme = () => {
    return THEME_COLORS.find(color => color.name === themeColor.name) || THEME_COLORS[0];
  };

  return (
    <Drawer
      PaperProps={{
        sx: {
          backdropFilter: 'blur(20px) saturate(200%)',
          background: darkMode 
            ? 'rgba(15, 23, 42, 0.8)' 
            : 'rgba(255, 255, 255, 0.8)',
          border: `1px solid ${darkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'}`,
          width: 360,
          boxShadow: '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
        }
      }}
      anchor="right" 
      open={themeDrawerOpen} 
      onClose={toggleThemeDrawer}
    >
      <Box sx={{ height: '100%', display: 'flex', flexDirection: 'column' }}>
        {/* Header */}
        <Box sx={{ p: 3, borderBottom: `1px solid ${darkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'}` }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', mb: 1 }}>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5 }}>
              <Box sx={{ 
                p: 1, 
                borderRadius: 2, 
                background: `linear-gradient(135deg, ${getCurrentTheme().primary}20, ${getCurrentTheme().secondary}20)`,
                border: `1px solid ${getCurrentTheme().primary}30`
              }}>
                <PaletteIcon sx={{ fontSize: 20, color: getCurrentTheme().primary }} />
              </Box>
              <Typography variant="h6" sx={{ fontWeight: 700, color: 'text.primary' }}>
                Theme Studio
              </Typography>
            </Box>
            <IconButton 
              onClick={toggleThemeDrawer}
              sx={{ 
                p: 1,
                '&:hover': { 
                  backgroundColor: darkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)' 
                }
              }}
            >
              <CloseIcon sx={{ fontSize: 18 }} />
            </IconButton>
          </Box>
          <Typography variant="body2" sx={{ color: 'text.secondary', fontSize: '0.875rem' }}>
            Customize your workspace appearance
          </Typography>
        </Box>

        <Box sx={{ flex: 1, overflow: 'auto', p: 3 }}>
          {/* Theme Mode Section */}
          <Box sx={{ mb: 4 }}>
            <Typography variant="subtitle2" sx={{ 
              mb: 2, 
              fontWeight: 600, 
              color: 'text.primary',
              display: 'flex',
              alignItems: 'center',
              gap: 1
            }}>
              <BrushIcon sx={{ fontSize: 16 }} />
              Appearance Mode
            </Typography>
            
            <Card className="bg-white/5 backdrop-blur-md border border-white/10">
              <CardBody className="p-4">
                <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                  <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                    {darkMode ? 
                      <DarkModeIcon sx={{ color: getCurrentTheme().primary, fontSize: 20 }} /> : 
                      <LightModeIcon sx={{ color: getCurrentTheme().primary, fontSize: 20 }} />
                    }
                    <Box>
                      <Typography variant="body2" sx={{ fontWeight: 600, color: 'text.primary' }}>
                        {darkMode ? 'Dark Mode' : 'Light Mode'}
                      </Typography>
                      <Typography variant="caption" sx={{ color: 'text.secondary' }}>
                        {darkMode ? 'Easy on the eyes' : 'Classic brightness'}
                      </Typography>
                    </Box>
                  </Box>
                  <Switch
                    checked={darkMode}
                    onChange={toggleDarkMode}
                    sx={{
                      '& .MuiSwitch-thumb': {
                        backgroundColor: getCurrentTheme().primary,
                      },
                      '& .MuiSwitch-track': {
                        backgroundColor: `${getCurrentTheme().primary}40`,
                      }
                    }}
                  />
                </Box>
              </CardBody>
            </Card>
          </Box>

          {/* Color Palette Section */}
          <Box sx={{ mb: 4 }}>
            <Typography variant="subtitle2" sx={{ 
              mb: 2, 
              fontWeight: 600, 
              color: 'text.primary',
              display: 'flex',
              alignItems: 'center',
              gap: 1
            }}>
              <ColorLensIcon sx={{ fontSize: 16 }} />
              Color Palette
            </Typography>
            
            <Box sx={{ display: 'grid', gap: 2 }}>
              {THEME_COLORS.map((color, index) => {
                const isSelected = color.name === themeColor.name;
                return (
                  <Card 
                    key={index}
                    className={`cursor-pointer transition-all duration-300 ${
                      isSelected 
                        ? 'bg-white/10 backdrop-blur-md border-2' 
                        : 'bg-white/5 backdrop-blur-md border border-white/10 hover:bg-white/10'
                    }`}
                    style={{ 
                      borderColor: isSelected ? color.primary : undefined,
                      boxShadow: isSelected ? `0 0 20px ${color.primary}30` : undefined
                    }}
                  >
                    <CardBody 
                      className="p-4 cursor-pointer"
                      onClick={() => toggleThemeColor(color)}
                    >
                      <Box sx={{ display: 'flex', alignItems: 'center', gap: 2 }}>
                        {/* Color Preview */}
                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                          <Box sx={{
                            width: 32,
                            height: 32,
                            borderRadius: 2,
                            background: `linear-gradient(135deg, ${color.primary}, ${color.secondary})`,
                            border: `2px solid ${isSelected ? color.primary : 'transparent'}`,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            transition: 'all 0.3s ease'
                          }}>
                            {isSelected && <CheckIcon sx={{ fontSize: 16, color: 'white' }} />}
                          </Box>
                          <Box sx={{
                            width: 16,
                            height: 16,
                            borderRadius: 1,
                            backgroundColor: color.secondary,
                            opacity: 0.7
                          }} />
                        </Box>
                        
                        {/* Color Info */}
                        <Box sx={{ flex: 1 }}>
                          <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mb: 0.5 }}>
                            <Typography variant="body2" sx={{ fontWeight: 600, color: 'text.primary' }}>
                              {color.name}
                            </Typography>
                            {isSelected && (
                              <Chip 
                                label="Active" 
                                size="small"
                                sx={{ 
                                  height: 18,
                                  fontSize: '0.65rem',
                                  backgroundColor: `${color.primary}20`,
                                  color: color.primary,
                                  fontWeight: 600
                                }}
                              />
                            )}
                          </Box>
                          <Typography variant="caption" sx={{ 
                            color: 'text.secondary',
                            fontSize: '0.75rem',
                            lineHeight: 1.3
                          }}>
                            {color.description}
                          </Typography>
                        </Box>
                      </Box>
                    </CardBody>
                  </Card>
                );
              })}
            </Box>
          </Box>

          {/* Background Pattern Section */}
          <Box sx={{ mb: 4 }}>
            <Typography variant="subtitle2" sx={{ 
              mb: 2, 
              fontWeight: 600, 
              color: 'text.primary',
              display: 'flex',
              alignItems: 'center',
              gap: 1
            }}>
              <WallpaperIcon sx={{ fontSize: 16 }} />
              Background Style
            </Typography>
            
            <Card className="bg-white/5 backdrop-blur-md border border-white/10">
              <CardBody className="p-4">
                <FormControl component="fieldset" sx={{ width: '100%' }}>
                  <RadioGroup
                    value={selectedBackground}
                    onChange={(e) => setSelectedBackground(e.target.value)}
                    sx={{ gap: 1 }}
                  >
                    {backgroundOptions.map((bg) => (
                      <FormControlLabel
                        key={bg.id}
                        value={bg.id}
                        control={
                          <Radio 
                            sx={{
                              color: getCurrentTheme().primary,
                              '&.Mui-checked': {
                                color: getCurrentTheme().primary,
                              },
                              '& .MuiSvgIcon-root': {
                                fontSize: 18,
                              }
                            }}
                          />
                        }
                        label={
                          <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, width: '100%' }}>
                            {/* Background Preview */}
                            <Box sx={{
                              width: 32,
                              height: 20,
                              borderRadius: 1,
                              border: `1px solid ${darkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.2)'}`,
                              overflow: 'hidden',
                              position: 'relative',
                              backgroundColor: bg.id === 'solid' ? 'var(--bg-color)' : 'transparent',
                              backgroundImage: 
                                bg.id === 'gradient' ? `linear-gradient(135deg, rgba(var(--theme-primary-rgb), 0.1), rgba(var(--theme-secondary-rgb), 0.1))` :
                                bg.id === 'pattern-1' ? `var(--bg-pattern-1)` :
                                bg.id === 'pattern-2' ? `var(--bg-pattern-2)` :
                                bg.id === 'pattern-3' ? `var(--bg-pattern-3)` :
                                bg.id === 'pattern-4' ? `var(--bg-pattern-4)` :
                                bg.id === 'pattern-5' ? `var(--bg-pattern-5)` :
                                bg.id === 'mesh' ? `var(--bg-pattern-mesh)` :
                                bg.id === 'waves' ? `var(--bg-pattern-waves)` : 'none',
                              backgroundSize: bg.id === 'waves' ? 'cover' : bg.id.includes('pattern') ? '50px 50px' : 'cover',
                            }} />
                            <Box>
                              <Typography variant="body2" sx={{ fontWeight: 600, color: 'text.primary' }}>
                                {bg.name}
                              </Typography>
                              <Typography variant="caption" sx={{ color: 'text.secondary', fontSize: '0.75rem' }}>
                                {bg.description}
                              </Typography>
                            </Box>
                          </Box>
                        }
                        sx={{
                          m: 0,
                          p: 1,
                          borderRadius: 1,
                          '&:hover': {
                            backgroundColor: darkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)'
                          }
                        }}
                      />
                    ))}
                  </RadioGroup>
                </FormControl>
              </CardBody>
            </Card>
          </Box>

          {/* Font Family Section */}
          <Box sx={{ mb: 3 }}>
            <Typography variant="subtitle2" sx={{ 
              mb: 2, 
              fontWeight: 600, 
              color: 'text.primary',
              display: 'flex',
              alignItems: 'center',
              gap: 1
            }}>
              <TextFieldsIcon sx={{ fontSize: 16 }} />
              Typography
            </Typography>
            
            <Card className="bg-white/5 backdrop-blur-md border border-white/10">
              <CardBody className="p-4">
                <FormControl component="fieldset" sx={{ width: '100%' }}>
                  <RadioGroup
                    value={selectedFont}
                    onChange={(e) => setSelectedFont(e.target.value)}
                    sx={{ gap: 1 }}
                  >
                    {fontOptions.map((font) => (
                      <FormControlLabel
                        key={font.id}
                        value={font.id}
                        control={
                          <Radio 
                            sx={{
                              color: getCurrentTheme().primary,
                              '&.Mui-checked': {
                                color: getCurrentTheme().primary,
                              },
                              '& .MuiSvgIcon-root': {
                                fontSize: 18,
                              }
                            }}
                          />
                        }
                        label={
                          <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, width: '100%' }}>
                            {/* Font Preview */}
                            <Box sx={{
                              minWidth: 40,
                              height: 24,
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              borderRadius: 1,
                              border: `1px solid ${darkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.2)'}`,
                              backgroundColor: darkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)',
                            }}>
                              <Typography 
                                variant="caption" 
                                sx={{ 
                                  fontFamily: font.family,
                                  fontSize: '0.7rem',
                                  fontWeight: 500,
                                  color: 'text.primary'
                                }}
                              >
                                Aa
                              </Typography>
                            </Box>
                            <Box>
                              <Typography 
                                variant="body2" 
                                sx={{ 
                                  fontWeight: 600, 
                                  color: 'text.primary',
                                  fontFamily: font.family
                                }}
                              >
                                {font.name}
                              </Typography>
                              <Typography variant="caption" sx={{ color: 'text.secondary', fontSize: '0.75rem' }}>
                                {font.description}
                              </Typography>
                            </Box>
                          </Box>
                        }
                        sx={{
                          m: 0,
                          p: 1,
                          borderRadius: 1,
                          '&:hover': {
                            backgroundColor: darkMode ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)'
                          }
                        }}
                      />
                    ))}
                  </RadioGroup>
                </FormControl>
              </CardBody>
            </Card>
          </Box>
        </Box>

        {/* Footer */}
        <Box sx={{ 
          p: 3, 
          borderTop: `1px solid ${darkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'}`,
          background: darkMode ? 'rgba(0, 0, 0, 0.2)' : 'rgba(255, 255, 255, 0.2)'
        }}>
          <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 1, mb: 1 }}>
            <AutoAwesomeIcon sx={{ fontSize: 14, color: getCurrentTheme().primary }} />
            <Typography variant="caption" sx={{ 
              color: 'text.secondary',
              fontSize: '0.75rem',
              fontWeight: 500
            }}>
              All changes are saved automatically
            </Typography>
          </Box>
          <Typography variant="caption" sx={{ 
            color: 'text.secondary',
            textAlign: 'center',
            display: 'block',
            fontSize: '0.7rem',
            opacity: 0.7
          }}>
            Personalize your workspace experience
          </Typography>
        </Box>
      </Box>
    </Drawer>
  );
};

export default ThemeSettingDrawer;
