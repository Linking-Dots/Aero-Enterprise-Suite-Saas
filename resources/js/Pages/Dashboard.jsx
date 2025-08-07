import { Head } from '@inertiajs/react';
import React, { useState, lazy, Suspense } from 'react';

// Lazy loaded components
const TimeSheetTable = lazy(() => import('@/Tables/TimeSheetTable.jsx'));
const UserLocationsCard = lazy(() => import('@/Components/UserLocationsCard.jsx'));
const UpdatesCards = lazy(() => import('@/Components/UpdatesCards.jsx'));
const HolidayCard = lazy(() => import('@/Components/HolidayCard.jsx'));
const StatisticCard = lazy(() => import('@/Components/StatisticCard.jsx'));
const PunchStatusCard = lazy(() => import('@/Components/PunchStatusCard.jsx'));
import App from "@/Layouts/App.jsx";
import { Grid, Box } from "@mui/material";
import { Spinner } from "@heroui/react";

import { 
    HomeIcon, 
    CalendarDaysIcon,
    ChartBarIcon 
} from '@heroicons/react/24/outline';

export default function Dashboard({ auth }) {

    const [updateMap, setUpdateMap] = useState(false);
    const [updateTimeSheet, setUpdateTimeSheet] = useState(false);
    const [selectedDate, setSelectedDate] = useState(new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Dhaka',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(new Date()));

    // Helper function to check permissions
    const hasPermission = (permission) => {
        return auth.permissions && auth.permissions.includes(permission);
    };

    // Helper function to check if user has any of the specified permissions
    const hasAnyPermission = (permissions) => {
        return permissions.some(permission => hasPermission(permission));
    };    

    const hasEveryPermission = (permissions) => {
        return permissions.every(permission => hasPermission(permission));
    };    
    
   
    
    const handlePunchSuccess = () => {
        setUpdateMap(prev => !prev);
        setUpdateTimeSheet(prev => !prev);
    };

    const handleDateChange = (event) => {
        const newDate = event.target.value;
        setSelectedDate(new Intl.DateTimeFormat('en-CA', {
            timeZone: 'Asia/Dhaka',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        }).format(new Date(newDate)));
        setUpdateTimeSheet(prev => !prev);
        setUpdateMap(prev => !prev);
    };

    return (
        <>
            <Head title="Dashboard" />
            <Box>
                {/*<NoticeBoard/>*/}
                <Suspense
                    fallback={
                        <div className="w-full flex justify-center items-center py-12">
                            <Spinner
                                classNames={{ label: "text-foreground mt-4" }}
                                label="Loading..."
                                variant="dots"
                            />
                        </div>
                    }
                >                    <Grid container>
                        {/* Punch Status Card - for employees and self-service users */}
                        {hasEveryPermission(['attendance.own.punch', 'attendance.own.view']) &&
                            <Grid item xs={12} md={6}>
                                <PunchStatusCard handlePunchSuccess={handlePunchSuccess} />
                            </Grid>
                        }
                        {/* Statistics Card - for users with dashboard access */}
                        {hasPermission('core.dashboard.view') &&
                            <Grid item xs={12} md={6}>
                                <StatisticCard />
                            </Grid>
                        }
                    </Grid>
                    
                    {/* Admin/Manager level components */}
                    {hasAnyPermission(['attendance.view', 'employees.view']) && (
                        <>
                            <TimeSheetTable selectedDate={selectedDate} handleDateChange={handleDateChange} updateTimeSheet={updateTimeSheet} />
                            <UserLocationsCard selectedDate={selectedDate} updateMap={updateMap} />
                        </>
                    )}
                    
                    {/* Updates and holidays - available to all authenticated users */}
                    {hasPermission('core.updates.view') && <UpdatesCards />}
                    {hasPermission('core.dashboard.view') && <HolidayCard />}
                </Suspense>
            </Box>
        </>
    );
}

Dashboard.layout = (page) => <App>{page}</App>;
