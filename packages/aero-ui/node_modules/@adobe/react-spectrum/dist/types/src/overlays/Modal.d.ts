import { AriaModalOverlayProps } from 'react-aria/useModalOverlay';
import { StyleProps } from '@react-types/shared';
import { OverlayProps } from './Overlay';
import { OverlayTriggerState } from 'react-stately/useOverlayTriggerState';
import React, { ReactNode } from 'react';
interface ModalProps extends AriaModalOverlayProps, StyleProps, Omit<OverlayProps, 'nodeRef' | 'shouldContainFocus'> {
    children: ReactNode;
    state: OverlayTriggerState;
    type?: 'modal' | 'fullscreen' | 'fullscreenTakeover';
}
export declare const Modal: React.ForwardRefExoticComponent<ModalProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
