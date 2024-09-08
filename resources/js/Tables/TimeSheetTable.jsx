import React, {useEffect, useState} from 'react';
import {
    Avatar,
    Box,
    CardContent,
    CardHeader,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Typography,
    Button,
    Grid,
    Badge,
    Chip,
    Collapse
} from '@mui/material';
import Grow from '@mui/material/Grow';
import GlassCard from "@/Components/GlassCard.jsx";
import {usePage} from "@inertiajs/react";
import RadioButtonCheckedIcon from "@mui/icons-material/RadioButtonChecked.js";

const TimeSheetTable = ({users}) => {

    const { todayLeaves } = usePage().props;
    const [attendances, setAttendances] = useState([]);
    const [error, setError] = useState('');

    const absentUsers = users.filter(user =>
        !attendances.some(attendance => attendance.user.id === user.id)
    );

    const [visibleUsersCount, setVisibleUsersCount] = useState(2);

    // Handle the load more click
    const handleLoadMore = () => {
        setVisibleUsersCount((prevCount) => prevCount + 2);
    };

    const getUserLeave = (userId) => {
        console.log(todayLeaves)
        return todayLeaves.find((leave) => leave.user_id === userId);
    };

    const getAllUserAttendanceForToday = async () => {


        try {
            const response = await fetch(route('getAllUsersAttendanceForToday'));
            const data = await response.json();

            if (response.ok) {
                setAttendances(data);
            } else {
                setError(data.message || 'Error fetching attendance data');
            }
        } catch (error) {
            console.error('Error fetching attendance data:', error);
            setError('An error occurred while retrieving attendance data.');
        }
    };

    useEffect(() => {
        getAllUserAttendanceForToday();
    }, []);


    return (
        <Box sx={{ display: 'flex', justifyContent: 'center', p: 2 }}>
            <Grid container spacing={2}>
                {/* Existing Attendance Table */}
                <Grid item xs={12} md={8}>
                    <Grow in>
                        <GlassCard>
                            <CardHeader title="Today's Timesheet" />
                            <CardContent>
                                {error ? (
                                    <Typography color="error">{error}</Typography>
                                ) : (
                                    <TableContainer>
                                        <Table>
                                            <TableHead sx={{
                                                fontWeight: 'bold',
                                                fontStyle: 'italic'
                                            }}>
                                                <TableRow>
                                                    <TableCell>Date</TableCell>
                                                    <TableCell>Employee</TableCell>
                                                    <TableCell>Clockin Time</TableCell>
                                                    {/*<TableCell>Clockin Location</TableCell>*/}
                                                    <TableCell>Clockout Time</TableCell>
                                                    <TableCell>Production Time</TableCell>
                                                </TableRow>
                                            </TableHead>
                                            <TableBody>
                                                {attendances.map((attendance, index) => {
                                                    // const punchinLat = attendance.punchin_location?.split(',')[0] || 'N/A';
                                                    // const punchinLng = attendance.punchin_location?.split(',')[1] || 'N/A';
                                                    // const punchoutLat = attendance.punchout_location?.split(',')[0] || 'N/A';
                                                    // const punchoutLng = attendance.punchout_location?.split(',')[1] || 'N/A';

                                                    return (
                                                        <TableRow key={index}>
                                                            <TableCell>{new Date(attendance.date).toLocaleString('en-US', {
                                                                month: 'long',
                                                                day: 'numeric',
                                                                year: 'numeric'
                                                            })}</TableCell>
                                                            <TableCell>
                                                                <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                                                    <Avatar
                                                                        src={attendance.user.profile_image}
                                                                        alt={attendance.user.name}
                                                                        sx={{ width: 24, height: 24, marginRight: 2 }}
                                                                    />
                                                                    <Typography>{attendance.user.name}</Typography>
                                                                </Box>
                                                            </TableCell>
                                                            <TableCell>{attendance.punchin_time ? new Date(`2024-06-04T${attendance.punchin_time}`).toLocaleTimeString('en-US', {
                                                                hour: 'numeric',
                                                                minute: '2-digit',
                                                                hour12: true
                                                            }) : 'N/A'}</TableCell>
                                                            <TableCell>{attendance.punchout_time ? new Date(`2024-06-04T${attendance.punchout_time}`).toLocaleTimeString('en-US', {
                                                                hour: 'numeric',
                                                                minute: '2-digit',
                                                                hour12: true
                                                            }) : 'N/A'}</TableCell>
                                                            <TableCell>
                                                                {attendance.punchin_time && attendance.punchout_time ? (
                                                                    (() => {
                                                                        const punchIn = new Date(`2024-06-04T${attendance.punchin_time}`);
                                                                        const punchOut = new Date(`2024-06-04T${attendance.punchout_time}`);
                                                                        const diffMs = punchOut - punchIn;
                                                                        const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
                                                                        const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                                                                        return `${diffHrs}h ${diffMins}m`;
                                                                    })()
                                                                ) : 'N/A'}
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })}
                                            </TableBody>
                                        </Table>
                                    </TableContainer>
                                )}
                            </CardContent>
                        </GlassCard>
                    </Grow>
                </Grid>
                <Grid item xs={12} md={4}>
                    <Grow in>
                        <GlassCard>
                            <CardHeader
                                title={
                                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                        {"Today Absent"}
                                        <Chip sx={{ml: 1}} label={absentUsers.length} variant="outlined" color="error" size="small" />
                                    </Box>
                                }
                            />
                            <CardContent>
                                <Box>
                                    {absentUsers.slice(0, visibleUsersCount).map((user, index) => {
                                        const userLeave = getUserLeave(user.id);
                                        return (
                                            <Collapse in={index < visibleUsersCount} key={index} timeout="auto" unmountOnExit>
                                                <Box sx={{ mb: 2, p: 2, border: '1px solid #e0e0e0', borderRadius: 2 }}>
                                                    <Box sx={{ display: 'flex', alignItems: 'center' }}>
                                                        <Avatar src="/assets/img/user.jpg" alt="User Image" />
                                                        <Box sx={{ ml: 2 }}>
                                                            <Typography variant="body1">{user.name}</Typography>
                                                        </Box>
                                                    </Box>
                                                    <Grid container alignItems="center" sx={{ mt: 2 }}>
                                                        <Grid item xs={6}>
                                                            {userLeave ?
                                                                <>
                                                                    <Typography variant="h6" sx={{ mb: 0 }}>
                                                                        {userLeave.from_date} to {userLeave.to_date}
                                                                    </Typography>
                                                                    <Typography variant="body2" color="textSecondary">Leave Date</Typography>
                                                                </> :
                                                                <Typography color="error" variant="h6" sx={{ mb: 0 }}>Absent without Leave</Typography>
                                                            }
                                                        </Grid>
                                                        {userLeave ? <Grid item xs={6} sx={{ textAlign: 'right' }}>
                                                            <Chip label={userLeave.status} variant="outlined" color={userLeave ? (userLeave.status === 'Pending' ? 'error' : 'success') : 'error'} size="small" />
                                                        </Grid> : ''}

                                                    </Grid>
                                                </Box>
                                            </Collapse>
                                        );
                                    })}

                                    {/* Only show the load more button if there are more users to load */}
                                    {visibleUsersCount < absentUsers.length && (
                                        <Box textAlign="center">
                                            <Button variant="outlined" onClick={handleLoadMore}>
                                                Load More
                                            </Button>
                                        </Box>
                                    )}
                                </Box>
                            </CardContent>
                        </GlassCard>
                    </Grow>
                </Grid>
            </Grid>



        </Box>
    );
};

export default TimeSheetTable;
