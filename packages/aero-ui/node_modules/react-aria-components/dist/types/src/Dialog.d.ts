import { AriaDialogProps } from 'react-aria/useDialog';
import { ContextValue, DOMRenderProps, SlotProps, StyleProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import { OverlayTriggerProps, OverlayTriggerState } from 'react-stately/useOverlayTriggerState';
import React, { JSX, ReactNode } from 'react';
export interface DialogTriggerProps extends OverlayTriggerProps {
    children: ReactNode;
}
export interface DialogRenderProps {
    close: () => void;
}
export interface DialogProps extends AriaDialogProps, StyleProps, SlotProps, DOMRenderProps<'section', undefined>, GlobalDOMAttributes<HTMLElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element.
     *
     * @default 'react-aria-Dialog'
     */
    className?: string;
    /** Children of the dialog. A function may be provided to access a function to close the dialog. */
    children?: ReactNode | ((opts: DialogRenderProps) => ReactNode);
}
export declare const DialogContext: React.Context<ContextValue<DialogProps, HTMLElement>>;
export declare const OverlayTriggerStateContext: React.Context<OverlayTriggerState | null>;
/**
 * A DialogTrigger opens a dialog when a trigger element is pressed.
 */
export declare function DialogTrigger(props: DialogTriggerProps): JSX.Element;
/**
 * A dialog is an overlay shown above other content in an application.
 */
export declare const Dialog: (props: DialogProps & React.RefAttributes<HTMLElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
