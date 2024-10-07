import * as React from 'react';
import {useState, useCallback, useEffect} from 'react';
import { styled } from '@mui/material/styles';
import Box from '@mui/material/Box';
import IconButton from '@mui/material/IconButton';
import Typography from '@mui/material/Typography';
import Menu from '@mui/material/Menu';
import MenuItem from '@mui/material/MenuItem';
import Container from '@mui/material/Container';
import Avatar from '@mui/material/Avatar';
import Tooltip from '@mui/material/Tooltip';
import Grow from '@mui/material/Grow';
import { AccountCircle, ExitToApp, Settings } from '@mui/icons-material';
import Brightness7Icon from "@mui/icons-material/Brightness7";
import Brightness4Icon from "@mui/icons-material/Brightness4";
import MenuIcon from '@mui/icons-material/Menu';
import CloseIcon from '@mui/icons-material/Close';
import KeyboardArrowDownIcon from '@mui/icons-material/KeyboardArrowDown';
import KeyboardArrowUpIcon from '@mui/icons-material/KeyboardArrowUp';
import {Collapse, Grid, Slide, Switch, useScrollTrigger} from "@mui/material";
import {Dropdown, DropdownTrigger, DropdownMenu, Button, DropdownItem, NavbarItem, Navbar, NavbarBrand, NavbarMenuToggle, NavbarMenuItem, NavbarMenu, NavbarContent, Accordion, AccordionItem} from "@nextui-org/react";
import logo from '../../../public/assets/images/logo.png';
import { Link, usePage } from '@inertiajs/react';
import GlassCard from "@/Components/GlassCard.jsx"
import GlassDropdown from "@/Components/GlassDropdown.jsx"
import useTheme from "@/theme.jsx";
import KeyboardArrowLeftIcon from '@mui/icons-material/KeyboardArrowLeft';

const useDeviceType = () => {
    const [isMobile, setIsMobile] = useState(false);
    const [isDesktop, setIsDesktop] = useState(false);

    const updateDeviceType = () => {
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        const isMobileUserAgent = /android|iphone|ipad|ipod/i.test(userAgent);

        if (window.innerWidth <= 768 || isMobileUserAgent) {
            setIsMobile(true);
            setIsDesktop(false);
        } else {
            setIsMobile(false);
            setIsDesktop(true);
        }
    };

    useEffect(() => {
        updateDeviceType();
        window.addEventListener('resize', updateDeviceType);

        // Cleanup event listener on component unmount
        return () => {
            window.removeEventListener('resize', updateDeviceType);
        };
    }, []);

    return { isMobile, isDesktop };
};

const Header = React.memo(({ darkMode, toggleDarkMode, sideBarOpen, toggleSideBar, url, pages }) => {
    const theme = useTheme(darkMode);
    const { auth } = usePage().props;
    const [activePage, setActivePage] = useState();
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const { isMobile, isDesktop } = useDeviceType();

    useEffect(() => {
        setActivePage(url);
    }, [url]);



    const settings = [
        { name: 'Settings', route: 'dashboard', icon: <Settings /> },
        { name: 'Logout', route: 'logout', method: 'post', icon: <ExitToApp /> }
    ];


    const trigger = useScrollTrigger();


    return (
        <>
            {isMobile &&
                <Box sx={{mb: 2}}>
                    <Grow in>
                        <Navbar
                            shouldHideOnScroll
                            isBordered
                            isMenuOpen={isMenuOpen}
                            onMenuOpenChange={setIsMenuOpen}
                        >
                            {/* Menu toggle for mobile */}
                            <NavbarContent justify="start">
                                <NavbarMenuToggle aria-label={isMenuOpen ? "Close menu" : "Open menu"} />
                            </NavbarContent>

                            {/* Mobile Logo Center */}
                            <NavbarContent className="pr-3" justify="center">
                                <NavbarBrand>
                                    <Box sx={{ flexGrow: 1}}>
                                        <Box
                                            component="img"
                                            alt="Logo"
                                            src={logo}
                                            sx={{
                                                height: '40px',
                                                width: '40px',
                                            }}
                                        />
                                    </Box>
                                </NavbarBrand>
                            </NavbarContent>

                            {/* Menu items for mobile */}
                            <NavbarMenu
                                className={darkMode ? 'dark' : 'light' }
                            >
                                {pages.map((page, index) => (
                                    <React.Fragment key={`${page.name}-${index}`}>
                                        {/* Main Page without submenu */}
                                        {!page.subMenu ? (
                                            <NavbarMenuItem
                                                className="px-2"
                                            >
                                                <Link
                                                    className={`
                                                        ${activePage === "/"+page.route ? 'shadow' : ''}
                                                        px-2 w-full py-4 hover:shadow rounded-lg h-14 flex items-center
                                                    `}
                                                    href={route(page.route)}
                                                    method={page.method || undefined}
                                                    size="sm"
                                                    color="foreground"
                                                    onClick={() => setIsMenuOpen(false)}
                                                >
                                                    {page.icon}
                                                    <span style={{ marginLeft: '8px' }}>{page.name}</span>
                                                </Link>
                                            </NavbarMenuItem>
                                        ) : (
                                            // Accordion for submenu
                                            <Accordion
                                                itemClasses={{
                                                    trigger: `
                                                        px-2 py-0
                                                        data-[hover=true]:shadow
                                                        data-[open=true]:shadow
                                                        rounded-lg h-14 flex items-center
                                                        ${page.subMenu && page.subMenu.find(subPage => "/" + subPage.route === activePage) ? 'shadow' : ''}
                                                    `,
                                                }}
                                            >
                                                <AccordionItem
                                                    key={`accordion-${index}`}
                                                    aria-label={`${page.name} submenu`}
                                                    startContent={page.icon}
                                                    title={page.name}
                                                    indicator={<KeyboardArrowLeftIcon/>}
                                                >
                                                    {page.subMenu.map((subPage, subIndex) => (
                                                        <NavbarMenuItem
                                                            key={`${subPage.name}-${subIndex}`}
                                                           >
                                                            <Link
                                                                className={`
                                                                    ${activePage === "/"+subPage.route ? 'shadow' : ''}
                                                                    px-2 w-full py-4 hover:shadow rounded-lg h-14 flex items-center
                                                                `}
                                                                href={route(subPage.route)}
                                                                method={page.method || undefined}
                                                                size="sm"
                                                                color="foreground"
                                                                onClick={() => setIsMenuOpen(false)}
                                                            >
                                                                {subPage.icon}
                                                                <span style={{ marginLeft: '8px' }}>{subPage.name}</span>
                                                            </Link>
                                                        </NavbarMenuItem>
                                                    ))}
                                                </AccordionItem>
                                            </Accordion>
                                        )}
                                    </React.Fragment>
                                ))}
                            </NavbarMenu>

                            {/* Profile or actions */}
                            <NavbarContent justify="end">
                                <NavbarItem>
                                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                            <GlassDropdown
                                                type='menu'
                                            >
                                                <DropdownTrigger>
                                                    <Tooltip title="Open settings">
                                                        <Avatar
                                                            alt={auth.user.first_name}
                                                            src={auth.user.profile_image} />
                                                    </Tooltip>
                                                </DropdownTrigger>

                                                <DropdownMenu>
                                                    <DropdownItem
                                                        as={Link}
                                                        href={route('profile', { user: auth.user.id })}
                                                        method="get"
                                                        key={'profile'}
                                                        // description="ACME scales apps to meet user demand, automagically, based on load."
                                                        startContent={<AccountCircle />}
                                                        style={{
                                                            color: theme.palette.text.primary,
                                                        }}
                                                    >
                                                        Profile
                                                    </DropdownItem>
                                                    {settings.map((setting) => (
                                                        <DropdownItem
                                                            as={Link}
                                                            href={route(setting.route)}
                                                            method={setting.method || undefined}
                                                            key={setting.name}
                                                            // description="ACME scales apps to meet user demand, automagically, based on load."
                                                            startContent={setting.icon}
                                                            style={{
                                                                color: theme.palette.text.primary,
                                                            }}
                                                        >
                                                            {setting.name}
                                                        </DropdownItem>
                                                    ))}
                                                    <DropdownItem
                                                        style={{ color: theme.palette.text.primary }}>
                                                        {darkMode ? <Brightness4Icon /> : <Brightness7Icon />}
                                                        <Switch checked={darkMode} onChange={toggleDarkMode} color="default" />
                                                    </DropdownItem>
                                                </DropdownMenu>
                                            </GlassDropdown>
                                        </Box>
                                    </Box>
                                </NavbarItem>
                            </NavbarContent>
                        </Navbar>
                    </Grow>
                </Box>
            }
            {isDesktop &&
                <Slide appear={false} direction="down" in={!trigger}>
                    <Box sx={{ p: 2 }}>
                        <Grow in>
                            <GlassCard>
                                <Container maxWidth="xl">
                                    <Box sx={{
                                        display: 'flex',
                                        flexDirection: 'row',
                                        alignItems: 'center', // Center items vertically
                                        p: {md: 2},
                                    }}>
                                        {/* Desktop Logo Area */}
                                        <Box sx={{
                                            display: {xs: 'none', md: 'flex'},
                                            flexDirection: 'row',
                                            alignItems: 'center',
                                            justifyContent: 'flex-start',
                                            flexGrow: 1,
                                        }}>
                                            <Box
                                                component="img"
                                                alt="Logo"
                                                src={logo}
                                                sx={{
                                                    display: { xs: 'none', md: 'flex' },
                                                    mr: 1,
                                                    height: '40px',
                                                    width: '40px',
                                                }}
                                            />
                                            <Typography
                                                variant="h6"
                                                noWrap
                                                component="a"
                                                href="#"
                                                sx={{
                                                    color: theme.palette.text.primary,
                                                    mr: 2,
                                                    display: { xs: 'none', md: 'flex' },
                                                    fontFamily: 'monospace',
                                                    fontWeight: 700,
                                                    letterSpacing: '.3rem',
                                                    textDecoration: 'none',
                                                }}
                                            >
                                                DBEDC
                                            </Typography>
                                            {!sideBarOpen && <IconButton
                                                sx={{ color: theme.palette.text.primary, display: { xs: 'none', md: 'flex' } }}
                                                size="large"
                                                onClick={toggleSideBar}
                                            >
                                                <MenuIcon />
                                            </IconButton> }
                                            {/* Desktop Menu Area */}
                                            <Collapse in={!sideBarOpen} timeout="auto" unmountOnExit>
                                                <Box sx={{ flexGrow: 1, display: { xs: 'none', md: 'flex' }, flexWrap: 'wrap'}}>
                                                    <Grid container>
                                                        {pages.map((page) => (
                                                            page.subMenu ? (
                                                                <Grid sx={{
                                                                    backgroundColor: page.subMenu.find(subPage => "/" + subPage.route === activePage) ? theme.palette.action.selected : 'transparent',
                                                                }} xs={12} sm={4} md={3} lg={3} item key={page.name} >
                                                                    <GlassDropdown type='menu'>
                                                                        <DropdownTrigger>
                                                                            <Button
                                                                                css={{
                                                                                    color: theme.palette.text.primary,
                                                                                }}
                                                                                startContent={page.icon}
                                                                                disableRipple
                                                                                className="p-0 bg-transparent data-[hover=true]:bg-transparent"
                                                                                endContent={<KeyboardArrowDownIcon />}
                                                                                radius="full"
                                                                                size="md"

                                                                            >
                                                                                {page.name}
                                                                            </Button>
                                                                        </DropdownTrigger>

                                                                        <DropdownMenu>
                                                                            {page.subMenu.map((subPage) => (
                                                                                <DropdownItem
                                                                                    as={Link}
                                                                                    href={route(subPage.route)}
                                                                                    method={subPage.method || undefined}
                                                                                    key={subPage.name}
                                                                                    // description="ACME scales apps to meet user demand, automagically, based on load."
                                                                                    startContent={subPage.icon}
                                                                                    style={{
                                                                                        color: theme.palette.text.primary,
                                                                                        backgroundColor: activePage === "/"+subPage.route ? theme.palette.action.selected : 'transparent',
                                                                                    }}
                                                                                    variant={'faded'}
                                                                                >
                                                                                    {subPage.name}
                                                                                </DropdownItem>
                                                                            ))}
                                                                        </DropdownMenu>
                                                                    </GlassDropdown>
                                                                </Grid>
                                                            ) : (
                                                                <Grid sx={{
                                                                    backgroundColor: activePage === "/"+page.route ? theme.palette.action.selected : 'transparent',
                                                                }} xs={12} sm={4} md={3} lg={3} item key={page.name}>
                                                                    <Button
                                                                        as={Link}
                                                                        href={route(page.route)}
                                                                        method={page.method || undefined}
                                                                        css={{
                                                                            color: theme.palette.text.primary,
                                                                        }}
                                                                        startContent={page.icon}
                                                                        disableRipple
                                                                        className="p-0 bg-transparent data-[hover=true]:bg-transparent"
                                                                        radius="full"
                                                                        size="md"
                                                                    >
                                                                        {page.name}
                                                                    </Button>
                                                                </Grid>
                                                            )
                                                        ))}
                                                    </Grid>
                                                </Box>
                                            </Collapse>
                                        </Box>

                                        {/* Profile Menu Area */}
                                        <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                            <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                                <GlassDropdown
                                                    type='menu'
                                                >
                                                    <DropdownTrigger>
                                                        <Tooltip title="Open settings">
                                                            <Avatar
                                                                alt={auth.user.first_name}
                                                                src={auth.user.profile_image} />
                                                        </Tooltip>
                                                    </DropdownTrigger>

                                                    <DropdownMenu>
                                                        <DropdownItem
                                                            as={Link}
                                                            href={route('profile', { user: auth.user.id })}
                                                            method="get"
                                                            key={'profile'}
                                                            // description="ACME scales apps to meet user demand, automagically, based on load."
                                                            startContent={<AccountCircle />}
                                                            style={{
                                                                color: theme.palette.text.primary,
                                                            }}
                                                        >
                                                            Profile
                                                        </DropdownItem>
                                                        {settings.map((setting) => (
                                                            <DropdownItem
                                                                as={Link}
                                                                href={route(setting.route)}
                                                                method={setting.method || undefined}
                                                                key={setting.name}
                                                                // description="ACME scales apps to meet user demand, automagically, based on load."
                                                                startContent={setting.icon}
                                                                style={{
                                                                    color: theme.palette.text.primary,
                                                                }}
                                                            >
                                                                {setting.name}
                                                            </DropdownItem>
                                                        ))}
                                                        <DropdownItem
                                                            style={{ color: theme.palette.text.primary }}>
                                                            {darkMode ? <Brightness4Icon /> : <Brightness7Icon />}
                                                            <Switch checked={darkMode} onChange={toggleDarkMode} color="default" />
                                                        </DropdownItem>
                                                    </DropdownMenu>
                                                </GlassDropdown>
                                            </Box>
                                        </Box>
                                    </Box>
                                </Container>
                            </GlassCard>
                        </Grow>
                    </Box>
                </Slide>
            }
        </>
    );
});

export default Header;
