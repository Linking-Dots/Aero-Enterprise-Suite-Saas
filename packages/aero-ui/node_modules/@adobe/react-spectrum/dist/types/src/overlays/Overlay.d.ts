import React, { MutableRefObject, ReactNode } from 'react';
export interface OverlayProps {
    children: ReactNode;
    isOpen?: boolean;
    container?: Element;
    isKeyboardDismissDisabled?: boolean;
    onEnter?: () => void;
    onEntering?: () => void;
    onEntered?: () => void;
    onExit?: () => void;
    onExiting?: () => void;
    onExited?: () => void;
    nodeRef: MutableRefObject<HTMLElement | null>;
    disableFocusManagement?: boolean;
    shouldContainFocus?: boolean;
}
export declare const Overlay: React.ForwardRefExoticComponent<OverlayProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
