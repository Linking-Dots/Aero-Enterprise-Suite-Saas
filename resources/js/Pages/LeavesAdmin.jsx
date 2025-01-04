import React, { useState, useEffect, useCallback } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Box,
    Button,
    CardContent,
    CardHeader,
    Grid, Typography,
} from '@mui/material';
import { Add } from '@mui/icons-material';
import Grow from '@mui/material/Grow';
import { Input } from '@nextui-org/react';
import SearchIcon from '@mui/icons-material/Search';
import GlassCard from '@/Components/GlassCard.jsx';
import App from '@/Layouts/App.jsx';
import LeaveEmployeeTable from '@/Tables/LeaveEmployeeTable.jsx';
import LeaveForm from '@/Forms/LeaveForm.jsx';
import DeleteLeaveForm from '@/Forms/DeleteLeaveForm.jsx';
import dayjs from 'dayjs';

const LeavesAdmin = ({ title, allUsers }) => {
    const { auth, props } = usePage();
    const [openModalType, setOpenModalType] = useState(null);
    const [leavesData, setLeavesData] = useState(props.leavesData);
    const [leaves, setLeaves] = useState();
    const [totalRows, setTotalRows] = useState(0);
    const [lastPage, setLastPage] = useState(0);
    const [leaveIdToDelete, setLeaveIdToDelete] = useState(null);
    const [employee, setEmployee] = useState('');
    const [selectedMonth, setSelectedMonth] = useState(
        dayjs().format('YYYY-MM')
    );
    const [perPage, setPerPage] = useState(30);
    const [currentPage, setCurrentPage] = useState(1);
    const [currentLeave, setCurrentLeave] = useState();
    const [error, setError] = useState('');

    const openModal = (modalType) => setOpenModalType(modalType);

    const handleClickOpen = useCallback((leaveId, modalType) => {
        setLeaveIdToDelete(leaveId);
        setOpenModalType(modalType);
    }, []);

    const handleClose = useCallback(() => {
        setOpenModalType(null);
        setLeaveIdToDelete(null);
    }, []);

    const handleSearch = (event) => setEmployee(event.target.value.toLowerCase());

    const handleMonthChange = (event) => {
        console.log(event.target.value);
        setSelectedMonth(event.target.value);
    };

    const fetchLeavesData = async () => {

        try {
            const response = await axios.get(route('leaves.paginate'), {
                params: {
                    page: currentPage,
                    perPage,
                    employee,
                    month: selectedMonth,
                },
            });

            console.log(selectedMonth, );

            if (response.status === 200) {
                const { data, total, last_page } = response.data.leaves;
                setLeaves(data);
                setTotalRows(total);
                setLastPage(last_page);
            }
        } catch (error) {
            console.error('Error fetching leaves data:', error);
            setError(error.response?.data?.message || 'Error retrieving data.');
        }
    };

    useEffect(() => {
        fetchLeavesData();
    }, [selectedMonth, currentPage, perPage, employee]);

    return (
        <>
            <Head title={title} />

            {['add_leave', 'edit_leave'].includes(openModalType) && (
                <LeaveForm
                    setTotalRows={setTotalRows}
                    setLastPage={setLastPage}
                    setLeaves={setLeaves}
                    perPage={perPage}
                    employee={employee}
                    currentPage={currentPage}
                    selectedMonth={selectedMonth}
                    allUsers={allUsers}
                    open={true}
                    setLeavesData={setLeavesData}
                    closeModal={() => setOpenModalType(null)}
                    leavesData={leavesData}
                    currentLeave={openModalType === 'edit_leave' ? currentLeave : null}
                    handleMonthChange={handleMonthChange}
                />
            )}

            {openModalType === 'delete_leave' && (
                <DeleteLeaveForm
                    open={true}
                    handleClose={handleClose}
                    leaveIdToDelete={leaveIdToDelete}
                    setLeavesData={setLeavesData}
                />
            )}

            <Box sx={{ display: 'flex', justifyContent: 'center', p: 2 }}>
                <Grow in>
                    <GlassCard>
                        <CardHeader
                            title="Leaves"
                            sx={{ padding: '24px' }}
                            action={
                                <Button
                                    title="Add Leave"
                                    variant="outlined"
                                    color="success"
                                    startIcon={<Add />}
                                    onClick={() => openModal('add_leave')}
                                >
                                    Add Leave
                                </Button>
                            }
                        />
                        <CardContent>
                            <Grid container spacing={2}>
                                <Grid item xs={12} sm={6} md={4}>
                                    <Input
                                        label="Search"
                                        fullWidth
                                        variant="bordered"
                                        placeholder="Employee..."
                                        value={employee}
                                        onChange={handleSearch}
                                        endContent={<SearchIcon />}
                                    />
                                </Grid>
                                <Grid item xs={12} sm={6} md={4}>
                                    <Input
                                        label="Select Month"
                                        type="month"
                                        variant="bordered"
                                        onChange={handleMonthChange}
                                        value={selectedMonth}
                                    />
                                </Grid>
                            </Grid>
                        </CardContent>
                        <CardContent>
                            {leaves ? (
                                <LeaveEmployeeTable
                                    totalRows={totalRows}
                                    lastPage={lastPage}
                                    setCurrentPage={setCurrentPage}
                                    setPerPage={setPerPage}
                                    perPage={perPage}
                                    currentPage={currentPage}
                                    handleClickOpen={handleClickOpen}
                                    setCurrentLeave={setCurrentLeave}
                                    openModal={openModal}
                                    leaves={leaves}
                                    allUsers={allUsers}
                                    setLeaves={setLeaves}
                                    employee={employee}
                                    selectedMonth={selectedMonth}
                                />
                            ) : error ? (
                                <Typography variant="body1" align="center">
                                    {error}
                                </Typography>
                            ) : (
                                <Typography variant="body1" align="center">
                                    Loading leaves data...
                                </Typography>
                            )}
                        </CardContent>
                    </GlassCard>
                </Grow>
            </Box>
        </>
    );
};

LeavesAdmin.layout = (page) => <App>{page}</App>;

export default LeavesAdmin;
