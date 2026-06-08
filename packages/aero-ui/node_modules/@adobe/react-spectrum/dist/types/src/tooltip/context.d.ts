import { PlacementAxis } from 'react-aria/useOverlayPosition';
import React, { HTMLAttributes } from 'react';
import { RefObject, StyleProps } from '@react-types/shared';
import { TooltipTriggerState } from 'react-stately/useTooltipTriggerState';
interface TooltipContextProps extends StyleProps {
    state?: TooltipTriggerState;
    ref?: RefObject<HTMLDivElement | null>;
    placement: PlacementAxis | null;
    arrowProps?: HTMLAttributes<HTMLElement>;
    arrowRef?: RefObject<HTMLElement | null>;
}
export declare const TooltipContext: React.Context<TooltipContextProps>;
export {};
