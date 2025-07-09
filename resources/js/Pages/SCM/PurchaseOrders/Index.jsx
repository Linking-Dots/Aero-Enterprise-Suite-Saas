import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
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
    ButtonGroup,
    Table,
    TableHeader,
    TableColumn,
    TableBody,
    TableRow,
    TableCell,
    Pagination,
    Dropdown,
    DropdownTrigger,
    DropdownMenu,
    DropdownItem
} from "@heroui/react";
import { 
    ShoppingCartIcon,
    PlusIcon,
    FunnelIcon,
    DocumentArrowDownIcon,
    EyeIcon,
    PencilIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
    TruckIcon,
    DocumentDuplicateIcon
} from "@heroicons/react/24/outline";
import { 
    MagnifyingGlassIcon,
    EllipsisVerticalIcon
} from '@heroicons/react/24/solid';
import GlassCard from '@/Components/GlassCard.jsx';
import PageHeader from '@/Components/PageHeader.jsx';
import StatsCards from '@/Components/StatsCards.jsx';
import App from '@/Layouts/App.jsx';
import dayjs from 'dayjs';
import axios from 'axios';
import { toast } from 'react-toastify';

const PurchaseOrdersIndex = () => {
    const { auth } = usePage().props;
    const theme = useTheme();
    const isMobile = useMediaQuery(theme.breakpoints.down('sm'));

    // State management
    const [loading, setLoading] = useState(false);
    const [purchaseOrders, setPurchaseOrders] = useState([]);
    const [statistics, setStatistics] = useState({
        total: 0,
        pending: 0,
        approved: 0,
        received: 0
    });
    const [totalRows, setTotalRows] = useState(0);
    const [currentPage, setCurrentPage] = useState(1);

    // Filters
    const [filters, setFilters] = useState({
        search: '',
        status: 'all',
        supplier: 'all',
        dateRange: 'all'
    });

    const [pagination, setPagination] = useState({
        perPage: 15,
        currentPage: 1
    });

    // Fetch purchase orders
    const fetchPurchaseOrders = useCallback(async () => {
        setLoading(true);
        try {
            const response = await axios.get('/api/scm/purchase-orders', {
                params: {
                    page: pagination.currentPage,
                    per_page: pagination.perPage,
                    ...filters
                }
            });

            if (response.data.success) {
                setPurchaseOrders(response.data.data.data);
                setTotalRows(response.data.data.total);
                setStatistics(response.data.statistics);
            }
        } catch (error) {
            console.error('Error fetching purchase orders:', error);
            toast.error('Failed to fetch purchase orders');
        } finally {
            setLoading(false);
        }
    }, [pagination, filters]);

    useEffect(() => {
        fetchPurchaseOrders();
    }, [fetchPurchaseOrders]);

    // Filter handlers
    const handleFilterChange = useCallback((filterKey, filterValue) => {
        setFilters(prev => ({
            ...prev,
            [filterKey]: filterValue
        }));
        setPagination(prev => ({ ...prev, currentPage: 1 }));
    }, []);

    const handlePageChange = useCallback((page) => {
        setPagination(prev => ({ ...prev, currentPage: page }));
    }, []);

    // Status color mapping
    const getStatusColor = (status) => {
        switch (status) {
            case 'draft': return 'default';
            case 'pending': return 'warning';
            case 'approved': return 'primary';
            case 'sent': return 'secondary';
            case 'received': return 'success';
            case 'cancelled': return 'danger';
            default: return 'default';
        }
    };

    // Priority color mapping
    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'low': return 'success';
            case 'medium': return 'warning';
            case 'high': return 'danger';
            case 'urgent': return 'danger';
            default: return 'default';
        }
    };

    // Statistics cards data
    const statsData = useMemo(() => [
        {
            title: 'Total Purchase Orders',
            value: statistics.total,
            icon: ShoppingCartIcon,
            color: 'primary',
            trend: '+12%'
        },
        {
            title: 'Pending Approval',
            value: statistics.pending,
            icon: ClockIcon,
            color: 'warning',
            trend: '+3%'
        },
        {
            title: 'Approved Orders',
            value: statistics.approved,
            icon: CheckCircleIcon,
            color: 'success',
            trend: '+8%'
        },
        {
            title: 'Received Orders',
            value: statistics.received,
            icon: TruckIcon,
            color: 'secondary',
            trend: '+15%'
        }
    ], [statistics]);

    // Actions handler
    const handleAction = (action, purchaseOrder) => {
        switch (action) {
            case 'view':
                router.visit(`/scm/purchase-orders/${purchaseOrder.id}`);
                break;
            case 'edit':
                router.visit(`/scm/purchase-orders/${purchaseOrder.id}/edit`);
                break;
            case 'approve':
                handleApprove(purchaseOrder.id);
                break;
            case 'cancel':
                handleCancel(purchaseOrder.id);
                break;
            case 'duplicate':
                handleDuplicate(purchaseOrder.id);
                break;
        }
    };

    const handleApprove = async (id) => {
        try {
            await axios.post(`/api/scm/purchase-orders/${id}/approve`);
            toast.success('Purchase order approved successfully');
            fetchPurchaseOrders();
        } catch (error) {
            toast.error('Failed to approve purchase order');
        }
    };

    const handleCancel = async (id) => {
        try {
            await axios.post(`/api/scm/purchase-orders/${id}/cancel`);
            toast.success('Purchase order cancelled successfully');
            fetchPurchaseOrders();
        } catch (error) {
            toast.error('Failed to cancel purchase order');
        }
    };

    const handleDuplicate = async (id) => {
        try {
            const response = await axios.post(`/api/scm/purchase-orders/${id}/duplicate`);
            if (response.data.success) {
                toast.success('Purchase order duplicated successfully');
                router.visit(`/scm/purchase-orders/${response.data.data.id}/edit`);
            }
        } catch (error) {
            toast.error('Failed to duplicate purchase order');
        }
    };

    return (
        <App>
            <Head title="Purchase Orders - SCM" />
            
            <Box sx={{ minHeight: '100vh', bgcolor: 'grey.50' }}>
                <PageHeader 
                    title="Purchase Orders"
                    subtitle="Manage procurement and purchasing operations"
                    icon={ShoppingCartIcon}
                    actions={
                        <Button
                            color="primary"
                            startContent={<PlusIcon className="w-4 h-4" />}
                            onPress={() => router.visit('/scm/purchase-orders/create')}
                        >
                            Create Purchase Order
                        </Button>
                    }
                />

                <Box sx={{ p: 3 }}>
                    {/* Statistics Cards */}
                    <StatsCards data={statsData} />

                    <Spacer y={6} />

                    {/* Filters */}
                    <GlassCard className="p-6">
                        <div className="flex flex-col lg:flex-row gap-4 items-end">
                            <Input
                                placeholder="Search purchase orders..."
                                startContent={<MagnifyingGlassIcon className="w-4 h-4 text-gray-400" />}
                                value={filters.search}
                                onChange={(e) => handleFilterChange('search', e.target.value)}
                                className="flex-1"
                            />
                            
                            <Select
                                placeholder="Status"
                                selectedKeys={[filters.status]}
                                onSelectionChange={(keys) => handleFilterChange('status', Array.from(keys)[0])}
                                className="w-full lg:w-48"
                            >
                                <SelectItem key="all">All Status</SelectItem>
                                <SelectItem key="draft">Draft</SelectItem>
                                <SelectItem key="pending">Pending</SelectItem>
                                <SelectItem key="approved">Approved</SelectItem>
                                <SelectItem key="sent">Sent</SelectItem>
                                <SelectItem key="received">Received</SelectItem>
                                <SelectItem key="cancelled">Cancelled</SelectItem>
                            </Select>

                            <Select
                                placeholder="Supplier"
                                selectedKeys={[filters.supplier]}
                                onSelectionChange={(keys) => handleFilterChange('supplier', Array.from(keys)[0])}
                                className="w-full lg:w-48"
                            >
                                <SelectItem key="all">All Suppliers</SelectItem>
                                {/* Supplier options would be loaded dynamically */}
                            </Select>

                            <Select
                                placeholder="Date Range"
                                selectedKeys={[filters.dateRange]}
                                onSelectionChange={(keys) => handleFilterChange('dateRange', Array.from(keys)[0])}
                                className="w-full lg:w-48"
                            >
                                <SelectItem key="all">All Time</SelectItem>
                                <SelectItem key="today">Today</SelectItem>
                                <SelectItem key="week">This Week</SelectItem>
                                <SelectItem key="month">This Month</SelectItem>
                                <SelectItem key="quarter">This Quarter</SelectItem>
                            </Select>

                            <Button
                                color="secondary"
                                variant="flat"
                                startContent={<DocumentArrowDownIcon className="w-4 h-4" />}
                            >
                                Export
                            </Button>
                        </div>
                    </GlassCard>

                    <Spacer y={6} />

                    {/* Purchase Orders Table */}
                    <GlassCard>
                        <CardHeader className="pb-0">
                            <Typography variant="h6" component="h2">
                                Purchase Orders
                            </Typography>
                        </CardHeader>
                        <CardBody className="overflow-x-auto">
                            {loading ? (
                                <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}>
                                    <CircularProgress />
                                </Box>
                            ) : (
                                <Table
                                    aria-label="Purchase orders table"
                                    classNames={{
                                        wrapper: "min-h-[400px]",
                                    }}
                                >
                                    <TableHeader>
                                        <TableColumn>PO Number</TableColumn>
                                        <TableColumn>Supplier</TableColumn>
                                        <TableColumn>Date</TableColumn>
                                        <TableColumn>Total</TableColumn>
                                        <TableColumn>Priority</TableColumn>
                                        <TableColumn>Status</TableColumn>
                                        <TableColumn>Actions</TableColumn>
                                    </TableHeader>
                                    <TableBody>
                                        {purchaseOrders.map((po) => (
                                            <TableRow key={po.id}>
                                                <TableCell>
                                                    <div className="font-medium text-primary">
                                                        {po.po_number}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <div>
                                                        <div className="font-medium">{po.supplier?.name}</div>
                                                        <div className="text-sm text-gray-500">{po.supplier?.email}</div>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {dayjs(po.po_date).format('MMM DD, YYYY')}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        ${parseFloat(po.total_amount).toLocaleString()}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Chip
                                                        color={getPriorityColor(po.priority)}
                                                        size="sm"
                                                        variant="flat"
                                                    >
                                                        {po.priority?.toUpperCase()}
                                                    </Chip>
                                                </TableCell>
                                                <TableCell>
                                                    <Chip
                                                        color={getStatusColor(po.status)}
                                                        size="sm"
                                                        variant="flat"
                                                    >
                                                        {po.status?.toUpperCase()}
                                                    </Chip>
                                                </TableCell>
                                                <TableCell>
                                                    <Dropdown>
                                                        <DropdownTrigger>
                                                            <Button
                                                                isIconOnly
                                                                size="sm"
                                                                variant="light"
                                                            >
                                                                <EllipsisVerticalIcon className="w-4 h-4" />
                                                            </Button>
                                                        </DropdownTrigger>
                                                        <DropdownMenu
                                                            onAction={(key) => handleAction(key, po)}
                                                        >
                                                            <DropdownItem
                                                                key="view"
                                                                startContent={<EyeIcon className="w-4 h-4" />}
                                                            >
                                                                View Details
                                                            </DropdownItem>
                                                            <DropdownItem
                                                                key="edit"
                                                                startContent={<PencilIcon className="w-4 h-4" />}
                                                            >
                                                                Edit
                                                            </DropdownItem>
                                                            {po.status === 'pending' && (
                                                                <DropdownItem
                                                                    key="approve"
                                                                    startContent={<CheckCircleIcon className="w-4 h-4" />}
                                                                    color="success"
                                                                >
                                                                    Approve
                                                                </DropdownItem>
                                                            )}
                                                            <DropdownItem
                                                                key="duplicate"
                                                                startContent={<DocumentDuplicateIcon className="w-4 h-4" />}
                                                            >
                                                                Duplicate
                                                            </DropdownItem>
                                                            {['draft', 'pending', 'approved'].includes(po.status) && (
                                                                <DropdownItem
                                                                    key="cancel"
                                                                    startContent={<XCircleIcon className="w-4 h-4" />}
                                                                    color="danger"
                                                                >
                                                                    Cancel
                                                                </DropdownItem>
                                                            )}
                                                        </DropdownMenu>
                                                    </Dropdown>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardBody>
                        {!loading && totalRows > 0 && (
                            <div className="flex justify-center p-4">
                                <Pagination
                                    total={Math.ceil(totalRows / pagination.perPage)}
                                    page={pagination.currentPage}
                                    onChange={handlePageChange}
                                    showControls
                                    showShadow
                                    color="primary"
                                />
                            </div>
                        )}
                    </GlassCard>
                </Box>
            </Box>
        </App>
    );
};

export default PurchaseOrdersIndex;
