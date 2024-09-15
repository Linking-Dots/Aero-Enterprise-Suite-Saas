import React, {forwardRef} from 'react';
import { Fade} from '@mui/material';
import {useTheme} from '@mui/material/styles';
import {Card} from "@nextui-org/react";

const GlassCard = forwardRef(({ children, ...props }, ref) => {
    const theme = useTheme();
    return (
        <Fade in>
            <Card isBlurred ref={ref} {...props} css={{
                height: '100%',
                width: '100%',
                // backdropFilter: 'blur(16px) saturate(200%)',
                // backgroundColor: theme.glassCard.backgroundColor,
                display: 'flex',
                flexDirection: 'column',
                position: 'relative',
                // borderRadius: '20px',
                minWidth: '0px',
                wordWrap: 'break-word',
                // boxShadow: '0px 2px 4px -1px rgba(0,0,0,0.2),0px 4px 5px 0px rgba(0,0,0,0.14),0px 1px 10px 0px rgba(0,0,0,0.12)',
                // backgroundClip: 'border-box',
            }}>
                {children}
            </Card>
        </Fade>

    )
});

export default GlassCard;
