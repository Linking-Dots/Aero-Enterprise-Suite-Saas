import React, { useState, useEffect, useCallback, useMemo, useRef } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Box,
    Typography,
    CircularProgress,
    Grow,
    useTheme,
    useMediaQuery,
    Grid,
} from '@mui/material';
import { 
    Select, 
    SelectItem, 
    Card, 
    CardBody, 
    CardHeader,
    Divider,
    Chip,
    Button,
    Input,
    Tabs,
    Tab,
    Spacer,
    ButtonGroup
} from "@heroui/react";
import { 
    CalendarIcon, 
    ChartBarIcon, 
    ClockIcon,
    UserIcon,
    PlusIcon,
    FunnelIcon,
    DocumentArrowDownIcon,
    Cog6ToothIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    PresentationChartLineIcon,
    AdjustmentsHorizontalIcon,

} from "@heroicons/react/24/outline";
import { 
    MagnifyingGlassIcon 
} from '@heroicons/react/24/solid';
import GlassCard from '@/Components/GlassCard.jsx';
import PageHeader from '@/Components/PageHeader.jsx';
import StatsCards from '@/Components/StatsCards.jsx';
import App from '@/Layouts/App.jsx';
import LeaveEmployeeTable from '@/Tables/LeaveEmployeeTable.jsx';
import LeaveForm from '@/Forms/LeaveForm.jsx';
import DeleteLeaveForm from '@/Forms/DeleteLeaveForm.jsx';
import BulkLeaveModal from '@/Components/BulkLeave/BulkLeaveModal.jsx';
import BulkDeleteModal from '@/Components/BulkDelete/BulkDeleteModal.jsx';
import dayjs from 'dayjs';
import axios from 'axios';
import { toast } from 'react-toastify';
import Fade from '@mui/material/Fade';


const LeavesAdmin = ({ title, allUsers }) => {
    const { auth } = usePage().props;
    const theme = useTheme();
    const isMobile = useMediaQuery(theme.breakpoints.down('sm'));
    const isTablet = useMediaQuery(theme.breakpoints.down('md'));

    // State management - Enhanced for admin view
    const [loading, setLoading] = useState(false);
    const [leavesData, setLeavesData] = useState([]);
    const [leaves, setLeaves] = useState();
    const [totalRows, setTotalRows] = useState(0);
    const [lastPage, setLastPage] = useState(0);
    const [currentLeave, setCurrentLeave] = useState();
    const [selectedLeavesForBulkDelete, setSelectedLeavesForBulkDelete] = useState([]);
    const [error, setError] = useState('');
    const [departments, setDepartments] = useState([]);

 


    // Pagination
    const [pagination, setPagination] = useState({
        perPage: 30,
        currentPage: 1
    });

    // Table-level loading spinner
    const [tableLoading, setTableLoading] = useState(false);

    // Show/Hide advanced filters panel
    const [showFilters, setShowFilters] = useState(false);


    const [filters, setFilters] = useState({
    employee: '',
    selectedMonth: dayjs().format('YYYY-MM'),
    status: [],
    leaveType: [],
    department: []
    });

    const handleFilterChange = useCallback((filterKey, filterValue) => {

    if (filterKey === 'year') {
        const year = Number(filterValue);
        if (year < 1900 || year > new Date().getFullYear()) {
        console.warn('Invalid year selected. Must be between 1900 and current year.');
        return;
        }
    }

    setFilters(prev => ({
        ...prev,
        [filterKey]: filterValue
    }));

    setPagination(prev => ({
        ...prev,
        currentPage: 1
    }));
    }, []);



    // Quick stats state
    const [leaveStats, setLeaveStats] = useState({
        pending: 0,
        approved: 0,
        rejected: 0,
        total: 0
    });

    // Check permissions using new system
    const canManageLeaves = auth.permissions?.includes('leaves.view') || false;
    const canApproveLeaves = auth.permissions?.includes('leaves.approve') || false;
    const canCreateLeaves = auth.permissions?.includes('leaves.create') || false;
    const canEditLeaves = auth.permissions?.includes('leaves.update') || false;
    const canDeleteLeaves = auth.permissions?.includes('leaves.delete') || false;

  

    const leaveTypeOptions = useMemo(() => {
        const defaultOptions = [{ key: 'all', label: 'All Types', value: 'all' }];

        if (!leavesData.leaveTypes) return defaultOptions;

        const dynamicOptions = leavesData.leaveTypes.map(leaveType => ({
            key: leaveType.type.toLowerCase(),
            label: leaveType.type,
            value: leaveType.type.toLowerCase()
        }));

        return [...defaultOptions, ...dynamicOptions];
    }, [leavesData.leaveTypes]);

    


    // Modal handlers
    const openModal = useCallback((modalType) => {
        setModalStates(prev => ({ ...prev, [modalType]: true }));
    }, []);

    const handleClickOpen = useCallback((leaveId, modalType) => {
        setCurrentLeave({ id: leaveId });
        setModalStates(prev => ({ ...prev, [modalType]: true }));
    }, []);


    const handleSearch = useCallback((event) => {
        handleFilterChange('employee', event.target.value.toLowerCase());
    }, [handleFilterChange]);

    const handleMonthChange = useCallback((event) => {
        handleFilterChange('selectedMonth', event.target.value);
    }, [handleFilterChange]);

    // Pagination handlers
    const handlePageChange = useCallback((page) => {
   
        setPagination(prev => ({
            ...prev,
            currentPage: page
        }));
    }, []);

    const handlePerPageChange = useCallback((newPerPage) => {
        setPagination(prev => ({
            ...prev,
            perPage: newPerPage,
            currentPage: 1
        }));
    }, []);

    const fetchLeavesData = useCallback(async (targetPage = null, targetPerPage = null) => {
    setLoading(true);
    const pageToFetch = targetPage || pagination.currentPage;
    const perPageToFetch = targetPerPage || pagination.perPage;
    
    try {
        const response = await axios.get(route('leaves.paginate'), {
            params: {
                page: pageToFetch,
                perPage: perPageToFetch,
                employee: filters.employee,
                month: filters.selectedMonth,
                status: Array.isArray(filters.status) && filters.status.length > 0 ? filters.status : undefined,
                leave_type: Array.isArray(filters.leaveType) && filters.leaveType.length > 0 ? filters.leaveType : undefined,
                department: Array.isArray(filters.department) && filters.department.length > 0 ? filters.department : undefined,
                admin_view: true, // Indicate this is an admin view
                view_all: true    // Request all users' leaves
            },
        });

        if (response.status === 200) {
            const { leaves, leavesData, departments } = response.data;

            if (leaves?.data && Array.isArray(leaves.data)) {
                setLeaves(leaves.data);
                setTotalRows(leaves.total || leaves.data.length);
                setLastPage(leaves.last_page || 1);
                
                // Update pagination state if we used different parameters
                if (targetPage && targetPage !== pagination.currentPage) {
                    setPagination(prev => ({
                        ...prev,
                        currentPage: targetPage
                    }));
                }
                if (targetPerPage && targetPerPage !== pagination.perPage) {
                    setPagination(prev => ({
                        ...prev,
                        perPage: targetPerPage
                    }));
                }
            } else if (Array.isArray(leaves)) {
                setLeaves(leaves);
                setTotalRows(leaves.length);
                setLastPage(1);
            } else {
                console.error('Unexpected leaves data format:', leaves);
                setLeaves([]);
                setTotalRows(0);
                setLastPage(1);
            }

            setLeavesData(leavesData);
            setDepartments(departments || []);
            setError('');
        }
    } catch (error) {
        console.error('Error fetching leaves data:', error.response);
        if (error.response?.status === 404) {
            const { leavesData } = error.response.data || {};
            setLeavesData(leavesData || []);
            setError(error.response?.data?.message || 'No leaves found for the selected criteria.');
        } else {
            setError(error.response?.data?.message || 'Error retrieving leaves data. Please try again.');
        }
        setLeaves([]);
        setTotalRows(0);
        setLastPage(1);
    } finally {
        setLoading(false);
    }
}, [filters, pagination.currentPage, pagination.perPage]);

    const fetchLeavesStats = useCallback(async () => {
        try {
            const response = await axios.get(route('leaves.stats'), {
                params: {
                    month: filters.selectedMonth,
                    admin_view: true, // Indicate this is an admin view
                    view_all: true    // Request stats for all users
                },
            });

            if (response.status === 200) {
                const { stats } = response.data;
                setLeaveStats(stats);
            }

        } catch (error) {
            console.error('Error fetching leaves data:', error.response);
            if (error.response?.status === 404) {
                setError(error.response?.data?.message || 'No leaves found for the selected criteria.');
            } else {
                setError('Error retrieving leaves data. Please try again.');
            }
            setLoading(false);
        }
    }, [filters]);

    // Bulk actions for admin
    const handleBulkApprove = useCallback(async (selectedLeaves) => {
        if (!canApproveLeaves) return;

        try {
            const response = await axios.post(route('leaves.bulk-approve'), {
                leave_ids: selectedLeaves
            });

            if (response.status === 200) {
                fetchLeavesData();
                const toastPromise = Promise.resolve();
                toast.promise(toastPromise, {
                    success: 'Selected leaves approved successfully'
                });
            }
        } catch (error) {
            console.error('Error bulk approving leaves:', error);
            const toastPromise = Promise.reject(error);
            toast.promise(toastPromise, {
                error: 'Failed to approve selected leaves'
            });
        }
    }, [canApproveLeaves, fetchLeavesData]);

    const handleBulkReject = useCallback(async (selectedLeaves) => {
        if (!canApproveLeaves) return;

        try {
            const response = await axios.post(route('leaves.bulk-reject'), {
                leave_ids: selectedLeaves
            });

            if (response.status === 200) {
                fetchLeavesData();
                const toastPromise = Promise.resolve();
                toast.promise(toastPromise, {
                    success: 'Selected leaves rejected successfully'
                });
            }
        } catch (error) {
            console.error('Error bulk rejecting leaves:', error);
            const toastPromise = Promise.reject(error);
            toast.promise(toastPromise, {
                error: 'Failed to reject selected leaves'
            });
        }
    }, [canApproveLeaves, fetchLeavesData]);

    // Handle bulk delete
    const handleBulkDelete = useCallback((selectedLeaves) => {
        setSelectedLeavesForBulkDelete(selectedLeaves);
        setModalStates(prev => ({ ...prev, bulk_delete: true }));
    }, []);

    

    // Prepare stats data for StatsCards component
    const statsData = useMemo(() => [
        {
            title: "Pending",
            value: leaveStats.pending,
            icon: <ClockIcon />,
            color: "text-orange-400",
            iconBg: "bg-orange-500/20",
            description: "Awaiting approval"
        },
        {
            title: "Approved", 
            value: leaveStats.approved,
            icon: <CheckCircleIcon />,
            color: "text-green-400",
            iconBg: "bg-green-500/20",
            description: "Successfully approved"
        },
        {
            title: "Rejected",
            value: leaveStats.rejected,
            icon: <XCircleIcon />,
            color: "text-red-400", 
            iconBg: "bg-red-500/20",
            description: "Declined requests"
        },
        {
            title: "Total",
            value: leaveStats.total,
            icon: <ChartBarIcon />,
            color: "text-blue-400",
            iconBg: "bg-blue-500/20", 
            description: "All leave requests"
        }
    ], [leaveStats]);

    // Early return if no permissions
    if (!canManageLeaves) {
        return (
            <Box sx={{ display: 'flex', justifyContent: 'center', p: 2 }}>
                <GlassCard>
                    <CardBody className="p-8 text-center">
                        <ExclamationTriangleIcon className="w-16 h-16 text-warning-500 mx-auto mb-4" />
                        <Typography variant="h6" className="mb-2">
                            Access Denied
                        </Typography>
                        <Typography variant="body2" color="textSecondary">
                            You don't have permission to view leave management.
                        </Typography>
                    </CardBody>
                </GlassCard>
            </Box>
        );
    }
    const [modalStates, setModalStates] = useState({
        add_leave: false,
        edit_leave: false,
        delete_leave: false,
        bulk_leave: false,
        bulk_delete: false,
    });
    const leaveTableRef = useRef(null);

    const openModalNew = useCallback((modalType) => {
        setModalStates(prev => ({ ...prev, [modalType]: true }));
    }, []);

    const closeModal = useCallback((modalType) => {
        setModalStates(prev => ({ ...prev, [modalType]: false }));
        // Clear selected leaves when closing bulk delete modal
        if (modalType === 'bulk_delete') {
            setSelectedLeavesForBulkDelete([]);
        }
    }, []);

    // Optimized data manipulation functions
    const sortLeavesByFromDate = useCallback((leavesArray) => {
        return [...leavesArray].sort((a, b) => new Date(b.from_date) - new Date(a.from_date));
    }, []);

     // Optimized pagination update without full reload
    const updatePaginationMetadata = useCallback((totalCount, affectedPage = null) => {
        // Update total rows
        setTotalRows(totalCount);
        
        // Calculate new last page
        const newLastPage = Math.max(1, Math.ceil(totalCount / pagination.perPage));
        setLastPage(newLastPage);
        
        // Ensure current page is valid
        if (pagination.currentPage > newLastPage) {
            setPagination(prev => ({
                ...prev,
                currentPage: newLastPage
            }));
        }
    }, [pagination.perPage, pagination.currentPage]);

  


    const leaveMatchesFilters = useCallback((leave) => {
        // Month filter
        const leaveMonth = dayjs(leave.from_date).format('YYYY-MM');
        if (filters.selectedMonth && leaveMonth !== filters.selectedMonth) return false;
        // Employee filter
        if (filters.employee) {
            const user = allUsers?.find(u => String(u.id) === String(leave.user_id));
            const filterValue = filters.employee.trim().toLowerCase();
            if (!user) {
                if (String(filters.employee) !== String(leave.user_id)) return false;
            } else if (
                String(user.id) !== filterValue &&
                !(user.name && user.name.trim().toLowerCase().includes(filterValue))
            ) {
                return false;
            }
        }
        // Status filter
        if (Array.isArray(filters.status) && filters.status.length > 0) {
            const matchesStatus = filters.status.some(status => String(leave.status).toLowerCase() === String(status).toLowerCase());
            if (!matchesStatus) return false;
        }
        // Leave type filter
        if (Array.isArray(filters.leaveType) && filters.leaveType.length > 0) {
            const matchesType = filters.leaveType.some(type => String(leave.leave_type).toLowerCase() === String(type).toLowerCase());
            if (!matchesType) return false;
        }
        // Department filter
        if (Array.isArray(filters.department) && filters.department.length > 0) {
            const user = allUsers?.find(u => String(u.id) === String(leave.user_id));
            if (!user) return false;
            const matchesDepartment = filters.department.some(depId => String(user.department_id) === String(depId));
            if (!matchesDepartment) return false;
        }
        return true;
    }, [filters, allUsers]);

    // Memoize leaves for table rendering
    const memoizedLeaves = useMemo(() => leaves || [], [leaves]);

    // Intelligently fetch additional items if needed without full reload
    const fetchAdditionalItemsIfNeeded = useCallback(async () => {
        // Only fetch if the number of displayed items is less than the perPage limit
        // This could happen after a deletion on any page, not just page 1.
        if (leaves && leaves.length < pagination.perPage && totalRows > leaves.length) {
            const itemsNeeded = Math.min(pagination.perPage - leaves.length, totalRows - leaves.length); // Don't fetch more than exist in total
            if (itemsNeeded <= 0) return;
            setTableLoading(true); // Show skeleton loader
            try {
                const response = await axios.get(route('leaves.paginate'), {
                    params: {
                        page: pagination.currentPage + 1, // Fetch the next page
                        perPage: itemsNeeded,          // Request only the needed items
                        employee: filters.employee,
                        month: filters.selectedMonth,
                        status: Array.isArray(filters.status) && filters.status.length > 0 ? filters.status : undefined,
                        leave_type: Array.isArray(filters.leaveType) && filters.leaveType.length > 0 ? filters.leaveType : undefined,
                        department: Array.isArray(filters.department) && filters.department.length > 0 ? filters.department : undefined,
                        admin_view: true, // Indicate this is an admin view
                        view_all: true    // Request all users' leaves
                    },
                });
                if (response.status === 200 && response.data.leaves.data) {
                    // Add these items to the current page, filtered
                    setLeaves(prevLeaves => {
                        const newItems = response.data.leaves.data.filter(leaveMatchesFilters);
                        const combinedLeaves = [...prevLeaves, ...newItems];
                        return sortLeavesByFromDate(combinedLeaves);
                    });
                }
            } catch (error) {
                const toastPromise = Promise.reject(error);
                toast.promise(toastPromise, {
                    error: 'Error fetching additional items.'
                });
                console.error(`Error fetching additional items from page ${pagination.currentPage + 1}:`, error);
            } finally {
                setTableLoading(false);
            }
        }
    }, [pagination.currentPage, pagination.perPage, leaves, filters, sortLeavesByFromDate, leaveMatchesFilters]);


     // Unified post-operation update handler for all CRUD operations
    const handlePostOperationUpdate = useCallback((operation, responseData) => {
        if (!responseData || !responseData.success) {
            console.error(`Invalid ${operation} response data:`, responseData);
            return;
        }

        // Always refresh stats for any operation
        fetchLeavesStats();

        // Update global leaves data if provided
        if (responseData.leavesData) {
            setLeavesData(responseData.leavesData);
        }

        const itemsPerPage = pagination.perPage;
        const currentPage = pagination.currentPage;

        switch (operation) {
            case 'bulk_delete': {
                const deletedLeaves = responseData.deleted_leaves || [];
                const deletedCount = responseData.deleted_count || deletedLeaves.length;
                
                if (deletedCount === 0) return;

                const deletedLeaveIds = deletedLeaves.map(leave => leave.id);
                const newTotal = Math.max(0, totalRows - deletedCount);
                const remainingOnCurrentPage = leaves.filter(leave => !deletedLeaveIds.includes(leave.id)).length;
                
                // Determine refresh strategy based on operation impact
                const shouldFullRefresh = (
                    deletedCount > 5 || // Large bulk operation
                    remainingOnCurrentPage === 0 || // Current page becomes empty
                    (currentPage > 1 && remainingOnCurrentPage < itemsPerPage / 2) // Page significantly depleted
                );

                if (shouldFullRefresh) {
                    // Calculate appropriate target page
                    const newLastPage = Math.ceil(newTotal / itemsPerPage);
                    let targetPage = currentPage;
                    
                    if (remainingOnCurrentPage === 0 && newTotal > 0) {
                        // If current page is empty but data exists, go to previous page or page 1
                        targetPage = Math.max(1, currentPage - 1);
                    } else if (currentPage > newLastPage && newLastPage > 0) {
                        // If current page exceeds new total pages, go to last page
                        targetPage = newLastPage;
                    }

                    // Update pagination and fetch fresh data
                    setPagination(prev => ({ ...prev, currentPage: targetPage }));
                    fetchLeavesData(targetPage, itemsPerPage);
                } else {
                    // Optimistic update for small deletions
                    setTableLoading(true);
                    setLeaves(prevLeaves => prevLeaves.filter(leave => !deletedLeaveIds.includes(leave.id)));
                    setTotalRows(newTotal);
                    updatePaginationMetadata(newTotal);
                    setTableLoading(false);
                    
                    // Fill page if needed
                    fetchAdditionalItemsIfNeeded();
                }
                break;
            }

            case 'single_delete': {
                const deletedLeaveId = responseData.deleted_leave_id;
                if (!deletedLeaveId) return;

                const newTotal = Math.max(0, totalRows - 1);
                const remainingOnCurrentPage = leaves.filter(leave => leave.id !== deletedLeaveId).length;

                if (remainingOnCurrentPage === 0 && newTotal > 0 && currentPage > 1) {
                    // Navigate to previous page if current page becomes empty
                    const targetPage = currentPage - 1;
                    setPagination(prev => ({ ...prev, currentPage: targetPage }));
                    fetchLeavesData(targetPage, itemsPerPage);
                } else {
                    // Optimistic update
                    setTableLoading(true);
                    setLeaves(prevLeaves => prevLeaves.filter(leave => leave.id !== deletedLeaveId));
                    setTotalRows(newTotal);
                    updatePaginationMetadata(newTotal);
                    setTableLoading(false);
                    
                    // Fill page if needed
                    fetchAdditionalItemsIfNeeded();
                }
                break;
            }

            case 'bulk_add':
            case 'single_add': {
                const addedCount = responseData.added_count || 1;
                const newTotal = totalRows + addedCount;
                
                // For bulk additions or if we're on the last page, refresh data
                if (operation === 'bulk_add' || (leaves.length < itemsPerPage)) {
                    setTotalRows(newTotal);
                    updatePaginationMetadata(newTotal);
                    fetchLeavesData(currentPage, itemsPerPage);
                } else {
                    // For single additions on full pages, just update total count
                    setTotalRows(newTotal);
                    updatePaginationMetadata(newTotal);
                }
                break;
            }

            case 'edit': {
                // For edits, optimistically update the specific item
                const updatedLeave = responseData.updated_leave || responseData.leave;
                if (updatedLeave) {
                    setLeaves(prevLeaves => 
                        prevLeaves.map(leave => 
                            leave.id === updatedLeave.id ? updatedLeave : leave
                        )
                    );
                }
                break;
            }
        }
    }, [
        pagination.perPage, 
        pagination.currentPage, 
        totalRows, 
        leaves, 
        fetchLeavesStats, 
        updatePaginationMetadata, 
        fetchLeavesData, 
        fetchAdditionalItemsIfNeeded
    ]);

    // Optimistic UI for add/edit
    // Single add handler using unified post-operation update
    const addLeaveOptimized = useCallback((newLeave) => {
        const responseData = {
            success: true,
            added_count: 1,
            leave: newLeave
        };
        handlePostOperationUpdate('single_add', responseData);
    }, [handlePostOperationUpdate]);

    // Update handler using unified post-operation update
    const updateLeaveOptimized = useCallback((updatedLeave) => {
        const responseData = {
            success: true,
            updated_leave: updatedLeave
        };
        handlePostOperationUpdate('edit', responseData);
        
        const toastPromise = Promise.resolve(['Leave updated!']);
        toast.promise(toastPromise, {
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
                    background: theme.glassCard.background,
                    border: theme.glassCard.border,
                    color: theme.palette.text.primary,
                },
            },
        });
    }, [fetchLeavesData, fetchLeavesStats, theme]);

    // Bulk add handler using unified post-operation update
    const addBulkLeavesOptimized = useCallback((responseData) => {
        if (!responseData || !responseData.success) {
            console.error('Invalid bulk response data:', responseData);
            return;
        }

        // Calculate added count from response data
        const createdLeaves = responseData.created_leaves || [];
        const addedCount = responseData.summary?.successful || createdLeaves.length;
        
        const updatedResponseData = {
            ...responseData,
            added_count: addedCount
        };
        
        handlePostOperationUpdate('bulk_add', updatedResponseData);
    }, [handlePostOperationUpdate]);


    

    // Single delete handler using unified post-operation update
    const deleteLeaveOptimized = useCallback((leaveId) => {
        // Create response data structure for the unified handler
        const responseData = {
            success: true,
            deleted_leave_id: leaveId
        };
        handlePostOperationUpdate('single_delete', responseData);
    }, [handlePostOperationUpdate]);

    // Optimistic UI for bulk deletion
   

    // Simplified bulk delete handler
    const deleteBulkLeavesOptimized = useCallback((responseData) => {
        handlePostOperationUpdate('bulk_delete', responseData);
    }, [handlePostOperationUpdate]);

   

    
    useEffect(() => {
        if (canManageLeaves) {
            // Only fetch data when page changes or filters change, not for every internal state update
            fetchLeavesData();
        }
    }, [fetchLeavesData, canManageLeaves, pagination.currentPage, filters.employee, filters.selectedMonth, filters.status, filters.leaveType, filters.department]);

    useEffect(() => {
        if (canManageLeaves) {
            fetchLeavesStats();
        }
    }, [fetchLeavesData, canManageLeaves]);

    


    return (
        <>
            <Head title={title} />

            {/* Modals - Enhanced for admin */}
            {modalStates.add_leave && (
                <LeaveForm
                    open={modalStates.add_leave}
                    closeModal={() => closeModal("add_leave")}
                    leavesData={leavesData}
                    setLeavesData={setLeavesData}
                    currentLeave={null}
                    allUsers={allUsers}
                    setTotalRows={setTotalRows}
                    setLastPage={setLastPage}
                    setLeaves={setLeaves}
                    handleMonthChange={handleMonthChange}
                    employee={filters.employee}
                    selectedMonth={filters.selectedMonth}
                    addLeaveOptimized={addLeaveOptimized}
                    fetchLeavesStats={fetchLeavesStats}

                />
            )}
            {modalStates.edit_leave && (
                <LeaveForm
                    open={modalStates.edit_leave}
                    closeModal={() => closeModal("edit_leave")}
                    leavesData={leavesData}
                    setLeavesData={setLeavesData}
                    currentLeave={currentLeave}
                    allUsers={allUsers}
                    setTotalRows={setTotalRows}
                    setLastPage={setLastPage}
                    setLeaves={setLeaves}
                    handleMonthChange={handleMonthChange}
                    employee={filters.employee}
                    selectedMonth={filters.selectedMonth}
                    updateLeaveOptimized={updateLeaveOptimized}
                    fetchLeavesStats={fetchLeavesStats}

                />
            )}

            {modalStates.delete_leave && (
                <DeleteLeaveForm
                    open={modalStates.delete_leave}
                    closeModal={() => closeModal("delete_leave")}
                    leaveId={currentLeave?.id}
                    setLeaves={setLeaves}
                    setTotalRows={setTotalRows}
                    setLastPage={setLastPage}
                    deleteLeaveOptimized={deleteLeaveOptimized}
                    fetchLeavesStats={fetchLeavesStats}
                />
            )}

            {modalStates.bulk_leave && (
                <BulkLeaveModal
                    open={modalStates.bulk_leave}
                    onClose={() => closeModal("bulk_leave")}
                    onSuccess={(responseData) => {
                        // Use the same optimization pattern as single leave
                        addBulkLeavesOptimized(responseData);
                    }}
                    allUsers={allUsers}
                    leavesData={leavesData}
                    isAdmin={true}
                />
            )}

            {modalStates.bulk_delete && (
                <BulkDeleteModal
                    open={modalStates.bulk_delete}
                    onClose={() => closeModal("bulk_delete")}
                    onSuccess={(responseData) => {
                        // Use the bulk deletion optimization
                        deleteBulkLeavesOptimized(responseData);
                    }}
                    selectedLeaves={selectedLeavesForBulkDelete}
                    allUsers={allUsers}
                />
            )}

            <Box sx={{ display: 'flex', justifyContent: 'center', p: 2 }}>
                <Grow in>
                    <GlassCard>
                        <PageHeader
                            title="Leave Management"
                            subtitle="Manage employee leave requests and approvals"
                            icon={<PresentationChartLineIcon className="w-8 h-8" />}
                            variant="gradient"
                            actionButtons={[
                                ...(canCreateLeaves ? [{
                                    label: "Add Leave",
                                    icon: <PlusIcon className="w-4 h-4" />,
                                    onPress: () => openModalNew('add_leave'),
                                    className: "bg-gradient-to-r from-[var(--theme-primary)] to-[var(--theme-secondary)] text-white font-medium hover:opacity-90"
                                }] : []),
                                ...(canCreateLeaves ? [{
                                    label: "Add Bulk",
                                    icon: <CalendarIcon className="w-4 h-4" />,
                                    onPress: () => openModalNew('bulk_leave'),
                                    className: "bg-gradient-to-r from-[var(--theme-primary)] to-[var(--theme-secondary)] text-white font-medium hover:opacity-90"
                                }] : []),
                                {
                                    label: "Export",
                                    icon: <DocumentArrowDownIcon className="w-4 h-4" />,
                                    variant: "bordered",
                                    className: "border-[rgba(var(--theme-primary-rgb),0.3)] bg-[rgba(var(--theme-primary-rgb),0.05)] hover:bg-[rgba(var(--theme-primary-rgb),0.1)]"
                                }
                            ]}
                        >
                            <div className="p-6">
                                {/* Quick Stats */}
                                <StatsCards stats={statsData} />
                                {/* Filters Section - Matching Employee View */}
                                <div className="flex flex-col sm:flex-row gap-4 mb-6">
                                    <div className="flex-1">
                                        <Input
                                            label="Search Employee"
                                            variant="bordered"
                                            placeholder="Search by name or ID..."
                                            value={filters.employee}
                                            onValueChange={value => handleFilterChange('employee', value)}
                                            startContent={<MagnifyingGlassIcon className="w-4 h-4" />}
                                            classNames={{
                                                input: "bg-transparent",
                                                inputWrapper: "bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/15",
                                            }}
                                            size={isMobile ? "sm" : "md"}
                                        />
                                    </div>
                                    <div className="flex gap-2 items-end">
                                        <ButtonGroup variant="bordered" className="bg-white/5">
                                            <Button
                                                isIconOnly={isMobile}
                                                color={showFilters ? 'primary' : 'default'}
                                                onPress={() => setShowFilters(!showFilters)}
                                                className={showFilters ? 'bg-purple-500/20' : 'bg-white/5'}
                                            >
                                                <AdjustmentsHorizontalIcon className="w-4 h-4" />
                                                {!isMobile && <span className="ml-1">Filters</span>}
                                            </Button>
                                        </ButtonGroup>
                                    </div>
                                </div>
                                {showFilters && (
                                    <Fade in={true} timeout={300}>
                                        <div className="mb-6 p-4 bg-white/5 backdrop-blur-md rounded-lg border border-white/10">
                                            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                                <Select
                                                label="Leave Status"
                                                selectionMode="multiple"
                                                variant="bordered"
                                                selectedKeys={filters.status}
                                                onSelectionChange={keys => handleFilterChange('status', Array.from(keys))}
                                                classNames={{ trigger: "bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/15" }}
                                                >
                                                <SelectItem key="pending" value="pending">Pending</SelectItem>
                                                <SelectItem key="approved" value="approved">Approved</SelectItem>
                                                <SelectItem key="rejected" value="rejected">Rejected</SelectItem>
                                                <SelectItem key="new" value="new">New</SelectItem>
                                                </Select>

                                                <Select
                                                label="Leave Type"
                                                variant="bordered"
                                                selectionMode="multiple"
                                                selectedKeys={filters.leaveType}
                                                onSelectionChange={keys => handleFilterChange('leaveType', Array.from(keys))}
                                                classNames={{ trigger: "bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/15" }}
                                                >
                                                {leaveTypeOptions.map(option => (
                                                    <SelectItem key={option.key} value={option.value}>{option.label}</SelectItem>
                                                ))}
                                                </Select>

                                                <Select
                                                label="Department"
                                                variant="bordered"
                                                selectionMode="multiple"
                                                selectedKeys={filters.department}
                                                onSelectionChange={keys => handleFilterChange('department', Array.from(keys))}
                                                classNames={{ trigger: "bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/15" }}
                                                >
                                                {departments.map(department => (
                                                    <SelectItem key={department.id} value={department.id}>{department.name}</SelectItem>
                                                ))}
                                                </Select>

                                                <Input
                                                    label="Month/Year"
                                                    type="month"
                                                    value={filters.selectedMonth}
                                                    onChange={handleMonthChange}
                                                    startContent={<CalendarIcon className="w-4 h-4" />}
                                                    variant="bordered"
                                                    classNames={{ inputWrapper: "bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/15" }}
                                                    size={isMobile ? "sm" : "md"}
                                                />
                                            </div>
                                            {/* Active Filters as Chips */}
                                            {(filters.employee || 
                                              (Array.isArray(filters.status) && filters.status.length > 0) || 
                                              (Array.isArray(filters.leaveType) && filters.leaveType.length > 0) || 
                                              (Array.isArray(filters.department) && filters.department.length > 0)) && (
                                                <div className="flex flex-wrap gap-2 mt-4 pt-4 border-t border-white/10">
                                                {filters.employee && (
                                                    <Chip variant="flat" color="primary" size="sm" onClose={() => handleFilterChange('employee', '')}>
                                                    Employee: {filters.employee}
                                                    </Chip>
                                                )}
                                                {Array.isArray(filters.status) && filters.status.map(stat => (
                                                    <Chip key={stat} variant="flat" color="secondary" size="sm" onClose={() => handleFilterChange('status', filters.status.filter(s => s !== stat))}>
                                                    Status: {stat}
                                                    </Chip>
                                                ))}
                                                {Array.isArray(filters.leaveType) && filters.leaveType.map(type => (
                                                    <Chip key={type} variant="flat" color="warning" size="sm" onClose={() => handleFilterChange('leaveType', filters.leaveType.filter(t => t !== type))}>
                                                    Type: {type}
                                                    </Chip>
                                                ))}
                                                {Array.isArray(filters.department) && filters.department.map(depId => {
                                                    const department = departments.find(dep => String(dep.id) === String(depId));
                                                    return (
                                                        <Chip key={depId} variant="flat" color="success" size="sm" onClose={() => handleFilterChange('department', filters.department.filter(d => d !== depId))}>
                                                            Department: {department?.name || `ID: ${depId}`}
                                                        </Chip>
                                                    );
                                                })}
                                                </div>
                                            )}
                                        </div>
                                    </Fade>
                                )}
                                {/* Table Section */}
                                <div className="min-h-96">
                                    <Typography variant="h6" className="mb-4 flex items-center gap-2">
                                        <ChartBarIcon className="w-5 h-5" />
                                        Leave Requests Management
                                    </Typography>

                                    {loading ? (
                                        <Card className="bg-white/10 backdrop-blur-md border-white/20">
                                            <CardBody className="text-center py-12">
                                                <CircularProgress size={40} />
                                                <Typography className="mt-4" color="textSecondary">
                                                    Loading leave data...
                                                </Typography>
                                            </CardBody>
                                        </Card>
                                    ) : leaves && leaves.length > 0 ? (
                                        <div className="overflow-hidden rounded-lg">
                                            <LeaveEmployeeTable
                                                ref={leaveTableRef}
                                                totalRows={totalRows}
                                                lastPage={lastPage}
                                                setCurrentPage={handlePageChange}
                                                setPerPage={handlePerPageChange}
                                                perPage={pagination.perPage}
                                                currentPage={pagination.currentPage}
                                                handleClickOpen={handleClickOpen}
                                                setCurrentLeave={setCurrentLeave}
                                                openModal={openModal}
                                                leaves={memoizedLeaves}
                                                allUsers={allUsers}
                                                setLeaves={setLeaves}
                                                employee={filters.employee}
                                                selectedMonth={filters.selectedMonth}
                                                isAdminView={true}
                                                onBulkApprove={handleBulkApprove}
                                                onBulkReject={handleBulkReject}
                                                canApproveLeaves={canApproveLeaves}
                                                canEditLeaves={canEditLeaves}
                                                    canDeleteLeaves={canDeleteLeaves}
                                                    fetchLeavesStats={fetchLeavesStats}
                                                    onBulkDelete={handleBulkDelete}
                                            />
                                        </div>
                                    ) : error ? (
                                        <Card className="bg-white/10 backdrop-blur-md border-white/20">
                                            <CardBody className="text-center py-12">
                                                <ExclamationTriangleIcon className="w-16 h-16 text-warning-500 mx-auto mb-4" />
                                                <Typography variant="h6" className="mb-2">
                                                    No Data Found
                                                </Typography>
                                                <Typography color="textSecondary">
                                                    {error}
                                                </Typography>
                                            </CardBody>
                                        </Card>
                                    ) : (
                                        <Card className="bg-white/10 backdrop-blur-md border-white/20">
                                            <CardBody className="text-center py-12">
                                                <CalendarIcon className="w-16 h-16 text-default-400 mx-auto mb-4" />
                                                <Typography variant="h6" className="mb-2">
                                                    No Leave Records Found
                                                </Typography>
                                                <Typography color="textSecondary">
                                                    No leave records found for the selected criteria.
                                                </Typography>
                                            </CardBody>
                                        </Card>
                                    )}
                                </div>
                            </div>
                        </PageHeader>
                    </GlassCard>
                </Grow>
            </Box>
        </>
    );
};

LeavesAdmin.layout = (page) => <App>{page}</App>;

export default LeavesAdmin;