import {
    Avatar,
    FormControl, IconButton,
    InputLabel,
    Menu, MenuItem, Select,
    Table,
    TableBody,
    TableCell, TableContainer,
    TableHead,
    TableRow,
    Typography
} from "@mui/material";
import { usePage, Link } from '@inertiajs/react';
import { Delete, Edit } from '@mui/icons-material';
import MoreVertIcon from "@mui/icons-material/MoreVert";
import React, {useState} from "react";
import {useTheme} from "@mui/material/styles";
import {toast} from "react-toastify";
import CircularProgress from "@mui/material/CircularProgress";



const EmployeeTable = ({allUsers, departments, designations}) => {
    const [users, setUsers] = useState(allUsers);
    const theme = useTheme();
    const [anchorEls, setAnchorEls] = useState({});

    async function handleChange(key, id, event) {
        const promise = new Promise(async (resolve, reject) => {
            try {
                const newValue = event.target.value;

                const response = await fetch(route('profile.update'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        id: id,
                        [key]: newValue,
                        // Add other fields here as needed, only if they have changed
                    }),
                });

                const data = await response.json();

                if (response.ok) {
                    setUsers((prevUsers) =>
                        prevUsers.map((user) => {
                            if (user.id === id) {
                                const updatedUser = {...user};

                                if (key === 'department' && user.department !== newValue) {
                                    updatedUser.designation = null;
                                }

                                updatedUser[key] = newValue;
                                return updatedUser;
                            }
                            return user;
                        })
                    );
                    resolve([...data.messages]);
                    console.log(data.messages);
                } else {
                    reject(data.messages);
                    console.error(data.messages);
                }
            } catch (error) {
                console.log(error)
                reject(['An unexpected error occurred.']);
            }
        });

        toast.promise(
            promise,
            {
                pending: {
                    render() {
                        return (
                            <div style={{display: 'flex', alignItems: 'center'}}>
                                <CircularProgress/>
                                <span style={{marginLeft: '8px'}}>Updating employee {key}...</span>
                            </div>
                        );
                    },
                    icon: false,
                    style: {
                        backdropFilter: 'blur(16px) saturate(200%)',
                        backgroundColor: theme.glassCard.backgroundColor,
                        border: theme.glassCard.border,
                        color: theme.palette.text.primary
                    }
                },
                success: {
                    render({data}) {
                        return (
                            <>
                                {data.map((message, index) => (
                                    <div key={index}>{message}</div>
                                ))}
                            </>
                        );
                    },
                    icon: '🟢',
                    style: {
                        backdropFilter: 'blur(16px) saturate(200%)',
                        backgroundColor: theme.glassCard.backgroundColor,
                        border: theme.glassCard.border,
                        color: theme.palette.text.primary
                    }
                },
                error: {
                    render({data}) {
                        return (
                            <>
                                {data.map((message, index) => (
                                    <div key={index}>{message}</div>
                                ))}
                            </>
                        );
                    },
                    icon: '🔴',
                    style: {
                        backdropFilter: 'blur(16px) saturate(200%)',
                        backgroundColor: theme.glassCard.backgroundColor,
                        border: theme.glassCard.border,
                        color: theme.palette.text.primary
                    }
                }
            }
        );
    }

    const handleDelete = async (userId) => {
        const promise = new Promise(async (resolve, reject) => {
            try {
                const response = await fetch(route('profile.delete'), {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_id: userId }),
                });

                const data = await response.json();

                if (response.ok) {
                    setUsers((prevUsers) => prevUsers.filter(user => user.id !== userId));
                    resolve([data.message]);
                } else {
                    reject([data.message]);
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                reject(['An error occurred while deleting user. Please try again.']);
            }
        });

        toast.promise(
            promise,
            {
                pending: {
                    render() {
                        return (
                            <div style={{display: 'flex', alignItems: 'center'}}>
                                <CircularProgress/>
                                <span style={{marginLeft: '8px'}}>Deleting user...</span>
                            </div>
                        );
                    },
                    icon: false,
                    style: {
                        backdropFilter: 'blur(16px) saturate(200%)',
                        backgroundColor: theme.glassCard.backgroundColor,
                        border: theme.glassCard.border,
                        color: theme.palette.text.primary,
                    },
                },
                success: {
                    render({ data }) {
                        return (
                            <>
                                {data.map((message, index) => (
                                    <div key={index}>{message}</div>
                                ))}
                            </>
                        );
                    },
                    icon: '🟢',
                    style: {
                        backdropFilter: 'blur(16px) saturate(200%)',
                        backgroundColor: theme.glassCard.backgroundColor,
                        border: theme.glassCard.border,
                        color: theme.palette.text.primary,
                    },
                },
                error: {
                    render({ data }) {
                        return (
                            <>
                                {data.map((message, index) => (
                                    <div key={index}>{message}</div>
                                ))}
                            </>
                        );
                    },
                    icon: '🔴',
                    style: {
                        backdropFilter: 'blur(16px) saturate(200%)',
                        backgroundColor: theme.glassCard.backgroundColor,
                        border: theme.glassCard.border,
                        color: theme.palette.text.primary,
                    },
                },
            }
        );
    };




    const handleClick = (event, id) => {
        setAnchorEls((prev) => ({ ...prev, [id]: event.currentTarget }));
    };

    const handleClose = (id) => {
        setAnchorEls((prev) => ({ ...prev, [id]: null }));
    };


    return (
        <TableContainer style={{ maxHeight: '70vh', overflowY: 'auto' }}>
            <Table aria-label="employee table">
                <TableHead>
                    <TableRow>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Employee ID</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Name</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Email</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Mobile</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Join Date</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Department</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Role</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }}>Reports To</TableCell>
                        <TableCell sx={{ whiteSpace: 'nowrap' }} align="right">
                            Action
                        </TableCell>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {users.map((user) => (
                        <TableRow key={user.id}>
                            <TableCell sx={{ whiteSpace: 'nowrap' }}>{user.employee_id || 'N/A'}</TableCell>
                            <TableCell sx={{ display: 'flex', alignItems: 'center', whiteSpace: 'nowrap' }}>
                                <Avatar
                                    src={`assets/images/users/${user.user_name}.jpg`}
                                    alt={user.first_name}
                                />
                                <Typography sx={{ marginLeft: '10px' }}>
                                    <Link
                                        sx={{ textDecoration: 'none' }}
                                        href={route('profile', { user: user.id })}
                                    >
                                        {user.name || 'N/A'}
                                    </Link>
                                    <br />
                                    {
                                        designations.find((designation) => designation.id === user.designation)?.title || 'N/A'
                                    }

                                </Typography>
                            </TableCell>
                            <TableCell sx={{ whiteSpace: 'nowrap' }}>{user.phone || 'N/A'}</TableCell>
                            <TableCell sx={{ whiteSpace: 'nowrap' }}>{user.email || 'N/A'}</TableCell>
                            <TableCell sx={{ whiteSpace: 'nowrap' }}>{user.date_of_joining || 'N/A'}</TableCell>
                            <TableCell sx={{ whiteSpace: 'nowrap' }}>
                                <FormControl size="small" fullWidth>
                                    <InputLabel id="department">Department</InputLabel>
                                    <Select
                                        labelId="department"
                                        id={`department-select-${user.id}`}
                                        value={user.department || 'na'}
                                        onChange={(event) => handleChange('department', user.id, event)}
                                        label="Department"
                                        MenuProps={{
                                            PaperProps: {
                                                sx: {
                                                    backdropFilter: 'blur(16px) saturate(200%)',
                                                    backgroundColor: theme.glassCard.backgroundColor,
                                                    border: theme.glassCard.border,
                                                    borderRadius: 2,
                                                    boxShadow:
                                                        'rgb(255, 255, 255) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px',
                                                },
                                            },
                                        }}
                                    >
                                        <MenuItem value="na" disabled>
                                            Select Department
                                        </MenuItem>
                                        {departments.map((dept) => (
                                            <MenuItem key={dept.id} value={dept.id}>
                                                {dept.name}
                                            </MenuItem>
                                        ))}
                                    </Select>
                                </FormControl>
                            </TableCell>

                            <TableCell sx={{ whiteSpace: 'nowrap' }}>
                                <FormControl size="small" fullWidth>
                                    <InputLabel id="designation">Designation</InputLabel>
                                    <Select
                                        labelId="designation"
                                        id={`designation-select-${user.id}`}
                                        value={user.designation || 'na'}
                                        onChange={(event) => handleChange('designation', user.id, event)}
                                        disabled={!user.department}
                                        label="Designation"
                                        MenuProps={{
                                            PaperProps: {
                                                sx: {
                                                    backdropFilter: 'blur(16px) saturate(200%)',
                                                    backgroundColor: theme.glassCard.backgroundColor,
                                                    border: theme.glassCard.border,
                                                    borderRadius: 2,
                                                    boxShadow:
                                                        'rgb(255, 255, 255) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px',
                                                },
                                            },
                                        }}
                                    >
                                        <MenuItem value="na" disabled>
                                            Select Designation
                                        </MenuItem>
                                        {designations
                                            .filter((designation) => designation.department_id === user.department)
                                            .map((desig) => (
                                                <MenuItem key={desig.id} value={desig.id}>
                                                    {desig.title}
                                                </MenuItem>
                                            ))}
                                    </Select>
                                </FormControl>
                            </TableCell>

                            {/*<TableCell sx={{ whiteSpace: 'nowrap' }}>*/}
                            {/*    <FormControl size="small" fullWidth>*/}
                            {/*        <InputLabel id="report_to">Reports To</InputLabel>*/}
                            {/*        <Select*/}
                            {/*            labelId="report_to"*/}
                            {/*            id={`report_to-select-${user.id}`}*/}
                            {/*            value={user.report_to || 'na'}*/}
                            {/*            onChange={(event) => handleChange('designation', user.id, event)}*/}
                            {/*            disabled={!user.department}*/}
                            {/*            label="Reports To"*/}
                            {/*            MenuProps={{*/}
                            {/*                PaperProps: {*/}
                            {/*                    sx: {*/}
                            {/*                        backdropFilter: 'blur(16px) saturate(200%)',*/}
                            {/*                        backgroundColor: theme.glassCard.backgroundColor,*/}
                            {/*                        border: theme.glassCard.border,*/}
                            {/*                        borderRadius: 2,*/}
                            {/*                        boxShadow:*/}
                            {/*                            'rgb(255, 255, 255) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px',*/}
                            {/*                    },*/}
                            {/*                },*/}
                            {/*            }}*/}
                            {/*        >*/}
                            {/*            <MenuItem value="na" disabled>*/}
                            {/*                --*/}
                            {/*            </MenuItem>*/}
                            {/*            {allUsers*/}
                            {/*                .filter((person) => person.department === user.department)*/}
                            {/*                .map((pers) => (*/}
                            {/*                    <MenuItem key={pers.id} value={pers.id}>*/}
                            {/*                        {pers.name}*/}
                            {/*                    </MenuItem>*/}
                            {/*                ))}*/}
                            {/*        </Select>*/}
                            {/*    </FormControl>*/}
                            {/*</TableCell>*/}

                            <TableCell sx={{ whiteSpace: 'nowrap' }} align="right">
                                <IconButton
                                    aria-controls={`simple-menu-${user.id}`}
                                    aria-haspopup="true"
                                    onClick={(event) => handleClick(event, user.id)}
                                >
                                    <MoreVertIcon />
                                </IconButton>
                                <Menu
                                    id={`simple-menu-${user.id}`}
                                    anchorEl={anchorEls[user.id]}
                                    keepMounted
                                    open={Boolean(anchorEls[user.id])}
                                    onClose={() => handleClose(user.id)}
                                    anchorOrigin={{
                                        vertical: 'bottom',
                                        horizontal: 'right',
                                    }}
                                    transformOrigin={{
                                        vertical: 'top',
                                        horizontal: 'right',
                                    }}
                                    sx={{
                                        '& .MuiMenu-paper': {
                                            backdropFilter: 'blur(16px) saturate(200%)',
                                            backgroundColor: theme.glassCard.backgroundColor,
                                            border: theme.glassCard.border,
                                            borderRadius: 2,
                                            boxShadow:
                                                'rgb(255, 255, 255) 0px 0px 0px 0px, rgba(0, 0, 0, 0.05) 0px 0px 0px 1px, rgba(0, 0, 0, 0.1) 0px 10px 15px -3px, rgba(0, 0, 0, 0.05) 0px 4px 6px -2px',
                                        },
                                    }}
                                >
                                    <MenuItem
                                        key={'Edit'}
                                        component={Link}
                                        href={route('profile', { user: user.id })}
                                        method="get"
                                        onClick={() => handleClose(user.id)}
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            color: 'inherit',
                                            textDecoration: 'none',
                                        }}
                                    >
                                        <Edit />
                                        <Typography sx={{ ml: 1 }} textAlign="center">
                                            Edit
                                        </Typography>
                                    </MenuItem>


                                    <MenuItem onClick={() => handleDelete(user.id)}>
                                        <Delete />
                                        <Typography sx={{ ml: 1 }} textAlign="center">
                                            Delete
                                        </Typography>
                                    </MenuItem>
                                </Menu>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </TableContainer>
    );
}

export default EmployeeTable;
