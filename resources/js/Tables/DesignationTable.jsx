import React, { useState, useCallback, useMemo } from 'react';
import { usePage } from "@inertiajs/react";
import { 
    Table, 
    TableBody, 
    TableCell, 
    TableColumn, 
    TableHeader, 
    TableRow, 
    User,
    Chip,
    Tooltip,
    Button,
    Dropdown,
    DropdownTrigger,
    DropdownMenu,
    DropdownItem,
    Pagination,
    Spinner
} from "@heroui/react";
import {
    PencilIcon,
    TrashIcon,
    EyeIcon,
    UsersIcon,
    BuildingOfficeIcon,
    BuildingOffice2Icon,
    CalendarIcon,
    MapPinIcon,
    CheckCircleIcon,
    XCircleIcon,
    EllipsisVerticalIcon
} from '@heroicons/react/24/outline';
import dayjs from 'dayjs';
import NoDataMessage from '@/Components/NoDataMessage';

const DesignationTable = ({ 
    designations, 
    onEdit, 
    onDelete, 
    onView, 
    loading, 
    isMobile,
    isTablet, 
    pagination, 
    onPageChange, 
    onRowsPerPageChange,
    canEditDesignation = false,
    canDeleteDesignation = false
}) => {
    // Check permissions directly from auth object
    const { auth } = usePage().props;
    const hasEditPermission = canEditDesignation || auth.permissions?.includes('designations.update') || false;
    const hasDeletePermission = canDeleteDesignation || auth.permissions?.includes('designations.delete') || false;
    
    // Set up columns based on screen size
    const columns = useMemo(() => {
        const baseColumns = [
            { name: "TITLE", uid: "title" },
            { name: "DEPARTMENT", uid: "department_name" },
            { name: "EMPLOYEES", uid: "employee_count" },
            { name: "STATUS", uid: "status" },
            { name: "ACTIONS", uid: "actions" }
        ];
        return baseColumns;
    }, []);

    // Render cell content
    const renderCell = (designation, columnKey) => {
        switch (columnKey) {
            case "title":
                return (
                    <span className="font-semibold text-foreground">{designation.title}</span>
                );
            case "department_name":
                return (
                    <span className="text-default-500">{designation.department_name || '-'}</span>
                );
            case "employee_count":
                return (
                    <div className="flex items-center gap-2">
                        <UsersIcon className="w-4 h-4 text-default-400" />
                        <span>{designation.employee_count || 0}</span>
                    </div>
                );
            case "status":
                return (
                    <Chip
                        size="sm"
                        variant={designation.is_active ? "solid" : "bordered"}
                        color={designation.is_active ? "success" : "danger"}
                        startContent={designation.is_active ?
                            <CheckCircleIcon className="w-3 h-3" /> :
                            <XCircleIcon className="w-3 h-3" />
                        }
                    >
                        {designation.is_active ? 'Active' : 'Inactive'}
                    </Chip>
                );
            case "actions":
                return (
                    <div className="flex justify-end items-center">
                        {!isMobile ? (
                            <>
                                <Tooltip content="View Designation">
                                    <Button
                                        isIconOnly
                                        size="sm"
                                        variant="light"
                                        className="text-default-400 hover:text-foreground"
                                        onPress={() => onView(designation)}
                                    >
                                        <EyeIcon className="w-4 h-4" />
                                    </Button>
                                </Tooltip>
                                
                                {hasEditPermission && (
                                    <Tooltip content="Edit Designation">
                                        <Button
                                            isIconOnly
                                            size="sm"
                                            variant="light"
                                            className="text-default-400 hover:text-blue-500"
                                            onPress={() => onEdit(designation)}
                                        >
                                            <PencilIcon className="w-4 h-4" />
                                        </Button>
                                    </Tooltip>
                                )}
                                
                                {hasDeletePermission && (
                                    <Tooltip 
                                        content={designation.employee_count > 0 ? 
                                            "Cannot delete designation with employees" : 
                                            "Delete Designation"
                                        }
                                    >
                                        <Button
                                            isIconOnly
                                            size="sm"
                                            variant="light"
                                            className="text-default-400 hover:text-danger"
                                            isDisabled={designation.employee_count > 0}
                                            onPress={() => onDelete(designation)}
                                        >
                                            <TrashIcon className="w-4 h-4" />
                                        </Button>
                                    </Tooltip>
                                )}
                            </>
                        ) : (
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
                                <DropdownMenu aria-label="Designation actions">
                                    <DropdownItem
                                        key="view"
                                        startContent={<EyeIcon className="w-4 h-4" />}
                                        onPress={() => onView(designation)}
                                    >
                                        View Details
                                    </DropdownItem>
                                    
                                    {hasEditPermission && (
                                        <DropdownItem
                                            key="edit"
                                            startContent={<PencilIcon className="w-4 h-4" />}
                                            onPress={() => onEdit(designation)}
                                        >
                                            Edit Designation
                                        </DropdownItem>
                                    )}
                                    
                                    {hasDeletePermission && (
                                        <DropdownItem
                                            key="delete"
                                            className="text-danger"
                                            color="danger"
                                            startContent={<TrashIcon className="w-4 h-4" />}
                                            isDisabled={designation.employee_count > 0}
                                            onPress={() => onDelete(designation)}
                                        >
                                            Delete Designation
                                        </DropdownItem>
                                    )}
                                </DropdownMenu>
                            </Dropdown>
                        )}
                    </div>
                );
                
            default:
                return <span>{designation[columnKey]}</span>;
        }
    };
    
    // Pagination component
    const renderPagination = () => {
        if (!designations || !designations.data || loading) return null;
        
        return (
            <div className="flex flex-col sm:flex-row items-center justify-between px-4 py-2 border-t border-white/10">
                <span className="text-xs text-default-400 mb-3 sm:mb-0">
                    Showing {((pagination.currentPage - 1) * pagination.perPage) + 1} to {
                        Math.min(pagination.currentPage * pagination.perPage, designations.total)
                    } of {designations.total} designations
                </span>
                
                <Pagination
                    total={Math.ceil(designations.total / pagination.perPage)}
                    initialPage={pagination.currentPage}
                    page={pagination.currentPage}
                    onChange={onPageChange}
                    size={isMobile ? "sm" : "md"}
                    variant="bordered"
                    showControls
                    classNames={{
                        item: "bg-white/10 backdrop-blur-md border-white/20 hover:bg-white/15",
                        cursor: "bg-white/20 backdrop-blur-md border-white/20",
                    }}
                />
            </div>
        );
    };
    
    // If no data and not loading
    if (!loading && (!designations || !designations.data || designations.data.length === 0)) {
        return (
            <div className="w-full">
                <NoDataMessage 
                    message="No designations found" 
                    icon={<BuildingOffice2Icon className="w-12 h-12 text-default-300" />}
                    description="Try adjusting your search or filters"
                />
            </div>
        );
    }
    
    return (
        <div className="w-full">
            <Table
                aria-label="Designations table"
                removeWrapper
                classNames={{
                    base: "bg-transparent",
                    th: "bg-white/5 backdrop-blur-md text-default-500 border-b border-white/10 font-medium text-xs",
                    td: "border-b border-white/5 py-3",
                }}
                bottomContent={renderPagination()}
            >
                <TableHeader columns={columns}>
                    {(column) => (
                        <TableColumn key={column.uid}>
                            {column.name}
                        </TableColumn>
                    )}
                </TableHeader>
                <TableBody
                    items={designations.data || []}
                    emptyContent={!loading && "No designations found"}
                    loadingContent={<Spinner />}
                    isLoading={loading}
                >
                    {(designation) => (
                        <TableRow key={designation.id}>
                            {(columnKey) => (
                                <TableCell>{renderCell(designation, columnKey)}</TableCell>
                            )}
                        </TableRow>
                    )}
                </TableBody>
            </Table>
        </div>
    );
};

export default DesignationTable;
