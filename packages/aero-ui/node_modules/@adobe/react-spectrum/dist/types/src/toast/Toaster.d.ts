import { AriaToastRegionProps } from 'react-aria/useToast';
import React, { ReactElement, ReactNode } from 'react';
import type { ToastPlacement } from './ToastContainer';
import { ToastState } from 'react-stately/useToastState';
interface ToastContainerProps extends AriaToastRegionProps {
    children: ReactNode;
    state: ToastState<unknown>;
    placement?: ToastPlacement;
}
export declare const ToasterContext: React.Context<boolean>;
export declare function Toaster(props: ToastContainerProps): ReactElement;
export {};
