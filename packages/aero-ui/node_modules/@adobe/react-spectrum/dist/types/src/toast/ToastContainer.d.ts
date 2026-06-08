import { AriaToastRegionProps } from 'react-aria/useToast';
import { DOMProps } from '@react-types/shared';
import { ReactElement } from 'react';
import { ToastOptions } from 'react-stately/useToastState';
export type ToastPlacement = 'top' | 'top end' | 'bottom' | 'bottom end';
export interface SpectrumToastContainerProps extends AriaToastRegionProps {
    placement?: ToastPlacement;
}
export interface SpectrumToastOptions extends ToastOptions, DOMProps {
    /** A label for the action button within the toast. */
    actionLabel?: string;
    /** Handler that is called when the action button is pressed. */
    onAction?: () => void;
    /** Whether the toast should automatically close when an action is performed. */
    shouldCloseOnAction?: boolean;
}
export type CloseFunction = () => void;
export declare function clearToastQueue(): void;
/**
 * A ToastContainer renders the queued toasts in an application. It should be placed
 * at the root of the app.
 */
export declare function ToastContainer(props: SpectrumToastContainerProps): ReactElement | null;
declare const SpectrumToastQueue: {
    /** Queues a neutral toast. */
    neutral(children: string, options?: SpectrumToastOptions): CloseFunction;
    /** Queues a positive toast. */
    positive(children: string, options?: SpectrumToastOptions): CloseFunction;
    /** Queues a negative toast. */
    negative(children: string, options?: SpectrumToastOptions): CloseFunction;
    /** Queues an informational toast. */
    info(children: string, options?: SpectrumToastOptions): CloseFunction;
};
export { SpectrumToastQueue as ToastQueue };
