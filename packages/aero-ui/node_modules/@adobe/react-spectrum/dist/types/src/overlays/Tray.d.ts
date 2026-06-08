import { AriaModalOverlayProps } from 'react-aria/useModalOverlay';
import { StyleProps } from '@react-types/shared';
import { OverlayProps } from './Overlay';
import { OverlayTriggerState } from 'react-stately/useOverlayTriggerState';
import React, { ReactNode } from 'react';
interface TrayProps extends AriaModalOverlayProps, StyleProps, Omit<OverlayProps, 'nodeRef' | 'shouldContainFocus'> {
    children: ReactNode;
    state: OverlayTriggerState;
    isFixedHeight?: boolean;
}
export declare const Tray: React.ForwardRefExoticComponent<TrayProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
