import React from 'react';
import { Typography, useMediaQuery, useTheme } from '@mui/material';
import { Button, Divider } from "@heroui/react";
import { GRADIENT_PRESETS, getIconGradientClasses, getTextGradientClasses } from '@/utils/gradientUtils.js';

/**
 * Theme-aware consistent page header component
 * @param {Object} props
 * @param {string} props.title - Page title
 * @param {string} props.subtitle - Page subtitle/description
 * @param {React.ReactNode} props.icon - Icon component for the header
 * @param {Array} props.actionButtons - Array of button configurations
 * @param {React.ReactNode} props.children - Additional content to render below header
 * @param {string} props.variant - Header variant: 'default', 'minimal', 'gradient'
 */
const PageHeader = ({ 
    title, 
    subtitle, 
    icon, 
    actionButtons = [], 
    children,
    variant = 'default'
}) => {
    const theme = useTheme();
    const isMobile = useMediaQuery(theme.breakpoints.down('sm'));    const getHeaderStyles = () => {
        switch (variant) {
            case 'minimal':
                return "bg-white/10 backdrop-blur-md border-b border-white/10";
            case 'gradient':
                return "bg-gradient-to-br from-white/20 via-white/10 to-transparent backdrop-blur-xl border-b border-white/20";
            default:
                return GRADIENT_PRESETS.pageHeader;
        }
    };    const getIconStyles = () => {
        return "p-3 rounded-xl " + GRADIENT_PRESETS.iconContainer + " backdrop-blur-sm";
    };    const getTitleStyles = () => {
        return GRADIENT_PRESETS.gradientText;
    };const getDefaultButtonStyles = () => {
        return GRADIENT_PRESETS.secondaryButton + " transition-all duration-300";
    };

    return (
        <div className="overflow-hidden theme-aware-header">
            {/* Header Section - Theme-aware */}
            <div className={getHeaderStyles()}>
                <div className="p-6">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div className="flex items-center gap-4">
                            {icon && (
                                <div className={getIconStyles()}>
                                    {React.cloneElement(icon, {
                                        style: { 
                                            color: 'var(--theme-primary)',
                                            ...icon.props.style 
                                        }
                                    })}
                                </div>
                            )}
                            <div>
                                <Typography 
                                    variant={isMobile ? "h5" : "h4"} 
                                    className={getTitleStyles()}
                                    style={{
                                        fontFamily: 'var(--font-current)',
                                        transition: 'all var(--transition)'
                                    }}
                                >
                                    {title}
                                </Typography>
                                {subtitle && (
                                    <Typography 
                                        variant="body2" 
                                        sx={{ 
                                            color: 'text.secondary',
                                            fontFamily: 'var(--font-current)',
                                            transition: 'all var(--transition)',
                                            mt: 0.5
                                        }}
                                    >
                                        {subtitle}
                                    </Typography>
                                )}
                            </div>
                        </div>
                        
                        {/* Action Buttons - Theme-aware */}
                        {actionButtons.length > 0 && (
                            <div className="flex gap-2 flex-wrap">
                                {actionButtons.map((button, index) => (
                                    <Button
                                        key={index}
                                        color={button.color || "primary"}
                                        variant={button.variant || "flat"}
                                        startContent={button.icon}
                                        onPress={button.onPress}
                                        isDisabled={button.isDisabled}
                                        className={
                                            button.className || getDefaultButtonStyles()
                                        }
                                        size={isMobile ? "sm" : "md"}
                                        style={{
                                            fontFamily: 'var(--font-current)',
                                            transition: 'all var(--transition)',
                                            ...button.style
                                        }}
                                    >
                                        {button.label}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
            
            {/* Divider - Theme-aware */}
            <Divider 
                className="border-[rgba(var(--theme-primary-rgb),0.1)]" 
                style={{ transition: 'all var(--transition)' }}
            />
            
            {/* Additional content */}
            {children && (
                <div className="theme-aware-content" style={{ transition: 'all var(--transition)' }}>
                    {children}
                </div>
            )}
        </div>
    );
};

export default PageHeader;
