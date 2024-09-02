import React, {useState} from 'react';
import {Head, Link, useForm} from '@inertiajs/react';
import {
    Box,
    CardContent,
    Checkbox,
    Container,
    FormControl,
    FormControlLabel,
    Grid,
    IconButton,
    InputAdornment,
    TextField,
    Typography
} from '@mui/material';
import LoadingButton from '@mui/lab/LoadingButton';
import Visibility from '@mui/icons-material/Visibility';
import VisibilityOff from '@mui/icons-material/VisibilityOff';
import logo from '../../../../public/assets/images/logo.png';
import App from '@/Layouts/App.jsx'
import Grow from '@mui/material/Grow';
import GlassCard from "@/Components/GlassCard.jsx";

import {useTheme} from '@mui/material/styles';
import FavoriteBorderIcon from '@mui/icons-material/FavoriteBorder';

const Login = () => {
    const theme = useTheme();
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login', {
        });
    };


    return (
        <App>
            <Head title="Login"/>
            <Box sx={{
                display: 'flex',
                justifyContent: 'center',
                alignItems: 'center',
                minHeight: '100vh',
                p: 2
            }}>
                <Grid container spacing={2} justifyContent="center">
                    <Grid item xs={12} md={8} lg={6} xl={5}>
                        <Grow in>
                            <GlassCard>

                                <CardContent>
                                    <Box textAlign="center">
                                        <Link style={{
                                            alignItems: 'center',
                                            display: 'inline-flex',

                                        }} href={route('dashboard')} className="mt-3 d-inline-block auth-logo">
                                            <img src={logo} alt="Logo" height="100"/>
                                        </Link>
                                        <Typography variant="h5" color="primary">Welcome Back!</Typography>
                                        <Typography variant="body2" color="text.secondary">Sign in to
                                            continue</Typography>
                                    </Box>
                                    <Box mt={4}>
                                        <form onSubmit={handleSubmit}>
                                            <Box mb={3}>
                                                <FormControl fullWidth>
                                                    <TextField
                                                        label="Email"
                                                        variant="outlined"
                                                        type="email"
                                                        id="email"
                                                        name="email"
                                                        value={data.email}
                                                        onChange={(e) => setData('email', e.target.value)}
                                                        required
                                                        autoFocus
                                                        fullWidth
                                                        error={!!errors.email}
                                                        helperText={errors.email}
                                                    />
                                                </FormControl>
                                            </Box>
                                            <Box mb={3}>
                                                <TextField
                                                    fullWidth
                                                    id="password"
                                                    label="Password"
                                                    type={showPassword ? 'text' : 'password'}
                                                    value={data.password}
                                                    onChange={(e) => setData('password', e.target.value)}
                                                    required
                                                    error={!!errors.password}
                                                    helperText={errors.password}
                                                    InputProps={{
                                                        endAdornment: (
                                                            <InputAdornment position="end">
                                                                <IconButton
                                                                    aria-label="toggle password visibility"
                                                                    onClick={() => setShowPassword(!showPassword)}
                                                                    onMouseDown={(e) => e.preventDefault()}
                                                                    edge="end"
                                                                >
                                                                    {showPassword ? <VisibilityOff/> :
                                                                        <Visibility/>}
                                                                </IconButton>
                                                            </InputAdornment>
                                                        ),
                                                    }}
                                                />
                                                <Box display="flex" justifyContent="space-between"
                                                     alignItems="center">
                                                    <Link href={route('password.request')} variant="body2"
                                                          color="text.secondary">
                                                        Forgot your password?
                                                    </Link>
                                                </Box>
                                            </Box>
                                            <FormControlLabel
                                                control={
                                                    <Checkbox
                                                        name="remember"
                                                        checked={data.remember}
                                                        onChange={(e) => setData('remember', e.target.checked)}
                                                        color="primary"
                                                    />
                                                }
                                                label="Remember me"
                                            />
                                            <Box mt={4}>
                                                <LoadingButton
                                                    fullWidth
                                                    variant="outlined"
                                                    color="primary"
                                                    type="submit"
                                                    loading={processing}
                                                >
                                                    Log in
                                                </LoadingButton>
                                            </Box>
                                        </form>
                                    </Box>
                                </CardContent>
                            </GlassCard>
                        </Grow>
                    </Grid>
                </Grid>
            </Box>
            <Box sx={{
                left: 0,
                right: 0,
                bottom: 10,
                position: 'fixed'
            }}>
                <Container>
                    <Grid container justifyContent="center">
                        <Grid item xs={12} textAlign="center">
                            <Typography sx={{ bottom: 0, display: 'flex', justifyContent: 'center'}} color="text.secondary">
                                &copy; {new Date().getFullYear()} Emam Hosen. Crafted with<FavoriteBorderIcon/>
                            </Typography>

                        </Grid>
                    </Grid>
                </Container>
            </Box>
        </App>

    );
};

export default Login;
