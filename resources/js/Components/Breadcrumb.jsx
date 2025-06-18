import React from 'react';
import {styled} from '@mui/material/styles';
import Breadcrumbs from '@mui/material/Breadcrumbs';
import Chip from '@mui/material/Chip';
import HomeIcon from '@mui/icons-material/Home';
import {Link, usePage} from '@inertiajs/react';
import {Box, Grid} from '@mui/material';
import Grow from '@mui/material/Grow';

const StyledBreadcrumb = styled(Chip)(({ theme }) => {
    const backgroundColor =
        theme.palette.mode === 'light'
            ? theme.palette.grey[100]
            : theme.palette.grey[800];
    return {
        backdropFilter: 'blur(16px) saturate(200%)',
        background: theme.glassCard.background,
        border: theme.glassCard.color,
        height: theme.spacing(3),
        color: theme.palette.text.primary,
        fontWeight: theme.typography.fontWeightRegular,
        '&:hover, &:focus': {
            cursor: 'pointer',
            backgroundColor: 'rgba(17, 25, 40, 0.7)',
        },
        '&:active': {
            boxShadow: theme.shadows[1],
            backgroundColor: 'rgba(17, 25, 40, 0.7)',
        },
    };
});


const Breadcrumb = ({ }) => {
    const {props} = usePage();
    const {title, auth} = props;
    return (
        <Box sx={{
            display: 'flex',
            justifyContent: 'center',
            padding: '0px 16px 0px 16px'
        }}>
            <Grow in>
                <Grid container>
                    <Grid item xs={12}>
                        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'right' }}>
                            <Box>
                                <Breadcrumbs aria-label="breadcrumb">
                                    <StyledBreadcrumb
                                        component="a"
                                        label="Home"
                                        icon={<HomeIcon fontSize="small" />}
                                    />
                                    <StyledBreadcrumb
                                        component={Link}
                                        href={route(route().current(), route().current() === 'profile' ? { user: auth.user.id } : {})}
                                        label={title}
                                    />
                                </Breadcrumbs>
                            </Box>
                        </Box>
                    </Grid>
                </Grid>
            </Grow>
        </Box>

    );
};

export default Breadcrumb;
