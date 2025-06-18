import React, { useState, useCallback, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Box,
    CardContent,
    Container,
    Grid,
    Typography,
    Alert,
    Fade,
    Zoom,
    Avatar,
    Stack,
    IconButton,
    Tooltip
} from '@mui/material';
import {
    Visibility,
    VisibilityOff,
    Email as EmailIcon,
    Lock as PasswordIcon,
    FavoriteBorder as FavoriteBorderIcon,
    Login as LoginIcon,
    Security,
    Shield,
    AdminPanelSettings
} from '@mui/icons-material';
import { useTheme, alpha } from '@mui/material/styles';
import logo from '../../../../public/assets/images/logo.png';
import App from '@/Layouts/App.jsx';
import GlassCard from "@/Components/GlassCard.jsx";
import { Input, Button } from "@heroui/react";
import { Link as NextLink } from "@heroui/react";

// Constants following ISO standards
const FORM_CONFIG = {
    EMAIL_REGEX: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    PASSWORD_MIN_LENGTH: 6,
    ANIMATION_DURATION: 800,
    DEBOUNCE_DELAY: 300
};

const VALIDATION_MESSAGES = {
    REQUIRED_EMAIL: 'Email address is required',
    INVALID_EMAIL: 'Please enter a valid email address',
    REQUIRED_PASSWORD: 'Password is required',
    SHORT_PASSWORD: `Password must be at least ${FORM_CONFIG.PASSWORD_MIN_LENGTH} characters`,
    LOGIN_FAILED: 'Invalid credentials. Please try again.',
    NETWORK_ERROR: 'Network error. Please check your connection.'
};

/**
 * Login Component - Enhanced with Hero UI and Glass morphism
 * Follows ISO standards for accessibility, security, and user experience
 */
const Login = () => {
    const theme = useTheme();

    // State management following React best practices
    const [formState, setFormState] = useState({
        email: '',
        password: '',
        showPassword: false,
        processing: false
    });
    const [errors, setErrors] = useState({});
    const [mounted, setMounted] = useState(false);

    // Component lifecycle
    React.useEffect(() => {
        setMounted(true);
    }, []);

    // Memoized validation functions
    const validateEmail = useCallback((email) => {
        if (!email.trim()) return VALIDATION_MESSAGES.REQUIRED_EMAIL;
        if (!FORM_CONFIG.EMAIL_REGEX.test(email)) return VALIDATION_MESSAGES.INVALID_EMAIL;
        return null;
    }, []);

    const validatePassword = useCallback((password) => {
        if (!password) return VALIDATION_MESSAGES.REQUIRED_PASSWORD;
        if (password.length < FORM_CONFIG.PASSWORD_MIN_LENGTH) return VALIDATION_MESSAGES.SHORT_PASSWORD;
        return null;
    }, []);

    // Memoized form validation
    const formValidation = useMemo(() => {
        const emailError = validateEmail(formState.email);
        const passwordError = validatePassword(formState.password);
        
        return {
            isValid: !emailError && !passwordError,
            errors: {
                email: emailError,
                password: passwordError
            }
        };
    }, [formState.email, formState.password, validateEmail, validatePassword]);

    // Form handlers with proper error handling
    const handleInputChange = useCallback((field, value) => {
        setFormState(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear specific field error when user starts typing
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: undefined
            }));
        }
    }, [errors]);

    const togglePasswordVisibility = useCallback(() => {
        setFormState(prev => ({
            ...prev,
            showPassword: !prev.showPassword
        }));
    }, []);

    const handleSubmit = useCallback(async (e) => {
        e.preventDefault();
        
        // Client-side validation
        if (!formValidation.isValid) {
            setErrors(formValidation.errors);
            return;
        }

        setFormState(prev => ({ ...prev, processing: true }));
        setErrors({});

        try {
            await router.post('/login', {
                email: formState.email.trim(),
                password: formState.password
            }, {
                preserveScroll: true,
                onError: (serverErrors) => {
                    setErrors(serverErrors);
                },
                onFinish: () => {
                    setFormState(prev => ({ ...prev, processing: false }));
                }
            });
        } catch (error) {
            console.error('Login error:', error);
            setErrors({ 
                general: VALIDATION_MESSAGES.NETWORK_ERROR 
            });
            setFormState(prev => ({ ...prev, processing: false }));
        }
    }, [formState.email, formState.password, formValidation.isValid]);

    const handleForgotPasswordClick = useCallback(() => {
        router.get(route('password.request'));
    }, []);

    // Memoized styles for performance
    const heroIconStyle = useMemo(() => ({
        width: 72,
        height: 72,
        background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.secondary.main})`,
        boxShadow: `0 12px 35px ${alpha(theme.palette.primary.main, 0.4)}`,
        backdropFilter: 'blur(16px) saturate(200%)',
        border: `2px solid ${alpha(theme.palette.primary.main, 0.2)}`,
        mb: 3,
        mx: 'auto'
    }), [theme]);

    const glassCardStyle = useMemo(() => ({
        backdropFilter: 'blur(20px) saturate(180%)',
        background: `linear-gradient(135deg, ${alpha(theme.palette.background.paper, 0.9)}, ${alpha(theme.palette.primary.main, 0.03)})`,
        border: `1px solid ${alpha(theme.palette.primary.main, 0.2)}`,
        borderRadius: 4,
        boxShadow: `0 25px 50px ${alpha(theme.palette.primary.main, 0.15)}`,
        overflow: 'hidden',
        position: 'relative'
    }), [theme]);

    return (
        <App>
            <Head title="Welcome Back - Login" />
            
            {/* Main Login Container */}
            <Box 
                sx={{
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    minHeight: '100vh',
                    p: 2,
                    background: `linear-gradient(135deg, ${alpha(theme.palette.primary.main, 0.02)}, ${alpha(theme.palette.secondary.main, 0.02)})`,
                    position: 'relative',
                    '&::before': {
                        content: '""',
                        position: 'absolute',
                        top: 0,
                        left: 0,
                        right: 0,
                        bottom: 0,
                        background: 'radial-gradient(circle at 30% 20%, rgba(120, 119, 198, 0.1) 0%, transparent 50%)',
                        pointerEvents: 'none'
                    }
                }}
            >
                <Grid container spacing={2} justifyContent="center" sx={{ zIndex: 1 }}>
                    <Grid item xs={12} sm={10} md={6} lg={5} xl={4}>
                        <Fade in={mounted} timeout={FORM_CONFIG.ANIMATION_DURATION}>
                            <GlassCard sx={glassCardStyle}>
                                {/* Hero Header Section */}
                                <Box
                                    sx={{
                                        position: 'relative',
                                        textAlign: 'center',
                                        pt: 4,
                                        pb: 2,
                                        px: 3
                                    }}
                                >
                                    {/* Hero Avatar with Logo */}
                                    <Zoom in={mounted} timeout={FORM_CONFIG.ANIMATION_DURATION + 200}>
                                        <Avatar sx={heroIconStyle}>
                                            <img 
                                                src={logo} 
                                                alt="Company Logo" 
                                                style={{ 
                                                    width: '80%', 
                                                    height: '80%', 
                                                    objectFit: 'contain',
                                                    filter: 'brightness(0) invert(1)'
                                                }} 
                                            />
                                        </Avatar>
                                    </Zoom>

                                    {/* Hero Title */}
                                    <Typography 
                                        variant="h3" 
                                        sx={{ 
                                            fontWeight: 800,
                                            background: `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.secondary.main})`,
                                            backgroundClip: 'text',
                                            WebkitBackgroundClip: 'text',
                                            color: 'transparent',
                                            fontSize: { xs: '1.8rem', sm: '2.2rem', md: '2.5rem' },
                                            mb: 1,
                                            letterSpacing: '-0.02em'
                                        }}
                                    >
                                        Welcome Back
                                    </Typography>

                                    {/* Hero Subtitle */}
                                    <Typography 
                                        variant="h6" 
                                        sx={{ 
                                            color: theme.palette.text.secondary,
                                            fontWeight: 400,
                                            fontSize: { xs: '1rem', sm: '1.1rem', md: '1.25rem' },
                                            mb: 1
                                        }}
                                    >
                                        Sign in to your workspace
                                    </Typography>

                                    {/* Security Indicators */}
                                    <Stack direction="row" spacing={1} justifyContent="center" sx={{ mt: 2 }}>
                                        <Tooltip title="Secure Login">
                                            <Avatar sx={{ 
                                                width: 32, 
                                                height: 32, 
                                                bgcolor: alpha(theme.palette.success.main, 0.1),
                                                border: `1px solid ${alpha(theme.palette.success.main, 0.2)}`
                                            }}>
                                                <Security sx={{ fontSize: 16, color: 'success.main' }} />
                                            </Avatar>
                                        </Tooltip>
                                        <Tooltip title="Data Protection">
                                            <Avatar sx={{ 
                                                width: 32, 
                                                height: 32, 
                                                bgcolor: alpha(theme.palette.info.main, 0.1),
                                                border: `1px solid ${alpha(theme.palette.info.main, 0.2)}`
                                            }}>
                                                <Shield sx={{ fontSize: 16, color: 'info.main' }} />
                                            </Avatar>
                                        </Tooltip>
                                        <Tooltip title="Enterprise Grade">
                                            <Avatar sx={{ 
                                                width: 32, 
                                                height: 32, 
                                                bgcolor: alpha(theme.palette.warning.main, 0.1),
                                                border: `1px solid ${alpha(theme.palette.warning.main, 0.2)}`
                                            }}>
                                                <AdminPanelSettings sx={{ fontSize: 16, color: 'warning.main' }} />
                                            </Avatar>
                                        </Tooltip>
                                    </Stack>
                                </Box>

                                <CardContent sx={{ px: 4, pb: 4 }}>
                                    {/* Error Alert */}
                                    {(errors.general || errors.email || errors.password) && (
                                        <Fade in timeout={300}>
                                            <Alert 
                                                severity="error" 
                                                sx={{ 
                                                    mb: 3,
                                                    background: alpha(theme.palette.error.main, 0.1),
                                                    border: `1px solid ${alpha(theme.palette.error.main, 0.2)}`,
                                                    backdropFilter: 'blur(10px)',
                                                    borderRadius: 2
                                                }}
                                            >
                                                {errors.general || 'Please check your credentials and try again.'}
                                            </Alert>
                                        </Fade>
                                    )}

                                    {/* Login Form */}
                                    <form onSubmit={handleSubmit} noValidate autoComplete="on">
                                        {/* Email Input */}
                                        <Box sx={{ mb: 3 }}>
                                            <Input
                                                isClearable
                                                type="email"
                                                label="Email Address"
                                                variant="underlined"
                                                id="email"
                                                name="email"
                                                value={formState.email}
                                                onChange={(e) => handleInputChange('email', e.target.value)}
                                                onClear={() => handleInputChange('email', '')}
                                                required
                                                autoFocus
                                                autoComplete="email"
                                                fullWidth
                                                isInvalid={!!(errors.email || formValidation.errors.email)}
                                                errorMessage={errors.email || formValidation.errors.email}
                                                placeholder="Enter your email address"
                                                labelPlacement="outside"
                                                startContent={
                                                    <EmailIcon sx={{ 
                                                        fontSize: 20, 
                                                        color: theme.palette.text.secondary 
                                                    }} />
                                                }
                                                classNames={{
                                                    input: "text-sm",
                                                    label: "font-semibold",
                                                    inputWrapper: [
                                                        "backdrop-blur-md",
                                                        "border-transparent",
                                                        "data-[hover=true]:border-primary/30",
                                                        "data-[focus=true]:border-primary",
                                                        "group-data-[focus=true]:border-primary"
                                                    ]
                                                }}
                                            />
                                        </Box>

                                        {/* Password Input */}
                                        <Box sx={{ mb: 4 }}>
                                            <Input
                                                label="Password"
                                                variant="underlined"
                                                placeholder="Enter your password"
                                                id="password"
                                                name="password"
                                                autoComplete="current-password"
                                                startContent={
                                                    <PasswordIcon sx={{ 
                                                        fontSize: 20, 
                                                        color: theme.palette.text.secondary 
                                                    }} />
                                                }
                                                endContent={
                                                    <IconButton
                                                        onClick={togglePasswordVisibility}
                                                        aria-label="toggle password visibility"
                                                        size="small"
                                                        sx={{ 
                                                            color: theme.palette.text.secondary,
                                                            '&:hover': {
                                                                color: theme.palette.primary.main
                                                            }
                                                        }}
                                                    >
                                                        {formState.showPassword ? 
                                                            <VisibilityOff sx={{ fontSize: 18 }} /> : 
                                                            <Visibility sx={{ fontSize: 18 }} />
                                                        }
                                                    </IconButton>
                                                }
                                                type={formState.showPassword ? "text" : "password"}
                                                value={formState.password}
                                                onChange={(e) => handleInputChange('password', e.target.value)}
                                                required
                                                isInvalid={!!(errors.password || formValidation.errors.password)}
                                                errorMessage={errors.password || formValidation.errors.password}
                                                labelPlacement="outside"
                                                classNames={{
                                                    input: "text-sm",
                                                    label: "font-semibold",
                                                    inputWrapper: [
                                                        "backdrop-blur-md",
                                                        "border-transparent",
                                                        "data-[hover=true]:border-primary/30",
                                                        "data-[focus=true]:border-primary",
                                                        "group-data-[focus=true]:border-primary"
                                                    ]
                                                }}
                                            />
                                        </Box>

                                        {/* Hero Login Button */}
                                        <Box sx={{ mb: 3 }}>
                                            <Button
                                                fullWidth
                                                size="lg"
                                                type="submit"
                                                isLoading={formState.processing}
                                                disabled={formState.processing}
                                                startContent={!formState.processing && <LoginIcon />}
                                                className="bg-gradient-to-r from-primary to-secondary text-white font-semibold"
                                                style={{
                                                    height: '48px',
                                                    background: formState.processing ? 
                                                        alpha(theme.palette.action.disabled, 0.12) : 
                                                        `linear-gradient(135deg, ${theme.palette.primary.main}, ${theme.palette.secondary.main})`,
                                                    backdropFilter: 'blur(16px) saturate(200%)',
                                                    border: `1px solid ${alpha(theme.palette.primary.main, 0.3)}`,
                                                    boxShadow: `0 8px 25px ${alpha(theme.palette.primary.main, 0.3)}`
                                                }}
                                            >
                                                {formState.processing ? 'Signing In...' : 'Sign In'}
                                            </Button>
                                        </Box>

                                        {/* Forgot Password Link */}
                                        <Box display="flex" justifyContent="center">
                                            <NextLink
                                                as="button"
                                                type="button"
                                                onClick={handleForgotPasswordClick}
                                                color="primary"
                                                className="text-sm font-medium"
                                                style={{
                                                    background: 'none',
                                                    border: 'none',
                                                    cursor: 'pointer'
                                                }}
                                            >
                                                Forgot your password?
                                            </NextLink>
                                        </Box>
                                    </form>
                                </CardContent>
                            </GlassCard>
                        </Fade>
                    </Grid>
                </Grid>
            </Box>

            {/* Enhanced Footer */}
            <Box 
                sx={{
                    position: 'fixed',
                    left: 0,
                    right: 0,
                    bottom: 0,
                    py: 2,
                    background: alpha(theme.palette.background.paper, 0.8),
                    backdropFilter: 'blur(20px) saturate(180%)',
                    borderTop: `1px solid ${alpha(theme.palette.divider, 0.1)}`
                }}
            >
                <Container>
                    <Grid container justifyContent="center">
                        <Grid item xs={12} textAlign="center">
                            <Typography 
                                variant="body2" 
                                sx={{ 
                                    display: 'flex', 
                                    justifyContent: 'center', 
                                    alignItems: 'center',
                                    gap: 1,
                                    color: theme.palette.text.secondary,
                                    fontSize: '0.875rem'
                                }}
                            >
                                &copy; {new Date().getFullYear()} Emam Hosen. Crafted with
                                <FavoriteBorderIcon sx={{ 
                                    fontSize: 16, 
                                    color: theme.palette.error.main 
                                }} />
                            </Typography>
                        </Grid>
                    </Grid>
                </Container>
            </Box>
        </App>
    );
};

export default Login;