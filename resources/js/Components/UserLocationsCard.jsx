import React, { useEffect, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import {Box, CardContent, CardHeader, CircularProgress, Typography} from '@mui/material';
import 'leaflet/dist/leaflet.css';
import 'leaflet-routing-machine';
import 'leaflet-fullscreen/dist/Leaflet.fullscreen.js'; // Import fullscreen control
import 'leaflet-fullscreen/dist/leaflet.fullscreen.css'; // Import fullscreen control CSS
import { usePage } from "@inertiajs/react";
import Grow from '@mui/material/Grow';
import GlassCard from "@/Components/GlassCard.jsx";
import L from 'leaflet';
import 'leaflet-routing-machine';
import {Avatar} from '@nextui-org/react';

const RoutingMachine = ({ startLocation, endLocation }) => {
    const map = useMap();

    useEffect(() => {
        if (map) {
            L.Routing.control({
                waypoints: [
                    L.latLng(startLocation),
                    L.latLng(endLocation)
                ],
                routeWhileDragging: true,
            }).addTo(map);
        }
    }, [map, startLocation, endLocation]);

    return null;
};

const UserMarkers = ({selectedDate}) => {
    const [users, setUsers] = useState(null);

    const initMap = async () => {
        const endpoint = route('getUserLocationsForDate', { date: selectedDate });

        try {
            const response = await fetch(endpoint);
            const data = await response.json();
            setUsers(data.locations);
        } catch (error) {
            console.error('Error fetching user locations:', error);
        }
    };

    useEffect(() => {
        initMap();
    }, [selectedDate]);

    const map = useMap();

    const getAdjustedPosition = (position, index) => {
        // Define a small offset
        const offset = 0.0001 * index; // Adjust multiplier as needed
        return {
            lat: position.lat + offset,
            lng: position.lng + offset,
        };
    };

    const arePositionsClose = (pos1, pos2) => {
        const threshold = 0.0001; // Adjust the threshold for "closeness"
        return (
            Math.abs(pos1.lat - pos2.lat) < threshold &&
            Math.abs(pos1.lng - pos2.lng) < threshold
        );
    };

    useEffect(() => {
        if (map && users) {
            users.map((user, index) => {
                const userIcon = L.icon({
                    iconUrl: `${user.profile_image}`,
                    iconSize: [30, 30],
                    className: 'user-icon',
                });

                const position = user.punchout_location
                    ? {
                        lat: parseFloat(user.punchout_location.split(',')[0]),
                        lng: parseFloat(user.punchout_location.split(',')[1]),
                    }
                    : user.punchin_location
                        ? {
                            lat: parseFloat(user.punchin_location.split(',')[0]),
                            lng: parseFloat(user.punchin_location.split(',')[1]),
                        }
                        : null;


                if (position) {
                    let adjustedPosition = position;

                    users.forEach((otherUser, otherIndex) => {
                        if (
                            otherIndex < index &&
                            otherUser.punchout_location &&
                            arePositionsClose(adjustedPosition, {
                                lat: parseFloat(otherUser.punchout_location.split(',')[0]),
                                lng: parseFloat(otherUser.punchout_location.split(',')[1]),
                            })
                        ) {
                            adjustedPosition = getAdjustedPosition(position, index);
                        }
                    });

                    L.marker(adjustedPosition, { icon: userIcon })
                        .addTo(map)
                        .bindPopup(
                            `Name: ${user.name}<br>Designation: ${user.designation}<br>Clockin Time: ${
                                user.punchin_time
                                    ? new Date(`2024-06-04T${user.punchin_time}`).toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true,
                                    })
                                    : 'Not punched in yet'
                            }<br>Clockout Time: ${
                                user.punchout_time
                                    ? new Date(`2024-06-04T${user.punchout_time}`).toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true,
                                    })
                                    : 'Not punched out yet'
                            }`
                        );
                }
                return null; // Returning null since map expects a return value
            });
        }
    }, [map, users]);

    return null;
};

const UserLocationsCard = ({updateMap, selectedDate}) => {

    const projectLocation = { lat: 23.879132, lng: 90.502617 };
    const startLocation = { lat: 23.987057, lng: 90.361908 };
    const endLocation = { lat: 23.690618, lng: 90.546729 };

    return (
        <Box sx={{ display: 'flex', justifyContent: 'center', p: 2 }}>
            <Grow in>
                <GlassCard>
                    <CardHeader title={
                        <Typography sx={{ fontSize: { xs: '1.0rem', sm: '1.4rem', md: '1.8rem' } }}>
                            {"Users Locations for " +
                                selectedDate.toLocaleString('en-US', {
                                    month: 'long',
                                    day: 'numeric',
                                    year: 'numeric'
                                })
                            }
                        </Typography>

                    } />
                    <CardContent>
                        <Box sx={{ height: '70vh', borderRadius: '20px' }}>
                            <MapContainer
                                key={updateMap}
                                center={projectLocation}
                                zoom={12}
                                style={{ height: '100%', width: '100%' }}
                                scrollWheelZoom={true} // Disable scrolling
                                doubleClickZoom={true} // Disable double-click zoom
                                dragging={false} // Allow dragging
                                fullscreenControl={true} // Enable fullscreen control
                                attributionControl={false}
                            >
                                <TileLayer
                                    url='https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'
                                    maxZoom={19}
                                />
                                <RoutingMachine startLocation={startLocation} endLocation={endLocation} />
                                <UserMarkers selectedDate={selectedDate}/>
                            </MapContainer>
                        </Box>
                    </CardContent>
                </GlassCard>
            </Grow>
        </Box>
    );
};

export default UserLocationsCard;

