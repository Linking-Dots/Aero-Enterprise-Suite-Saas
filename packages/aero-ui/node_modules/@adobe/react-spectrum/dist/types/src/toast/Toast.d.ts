import AlertMedium from '@spectrum-icons/ui/AlertMedium';
import { DOMProps } from '@react-types/shared';
import InfoMedium from '@spectrum-icons/ui/InfoMedium';
import { QueuedToast, ToastState } from 'react-stately/useToastState';
import React from 'react';
import SuccessMedium from '@spectrum-icons/ui/SuccessMedium';
export interface SpectrumToastValue extends DOMProps {
    children: string;
    variant: 'positive' | 'negative' | 'info' | 'neutral';
    actionLabel?: string;
    onAction?: () => void;
    shouldCloseOnAction?: boolean;
}
export interface SpectrumToastProps {
    toast: QueuedToast<SpectrumToastValue>;
    state: ToastState<SpectrumToastValue>;
}
export declare const ICONS: {
    info: typeof InfoMedium;
    negative: typeof AlertMedium;
    positive: typeof SuccessMedium;
};
export declare const Toast: React.ForwardRefExoticComponent<SpectrumToastProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
