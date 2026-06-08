import { AriaDialogProps } from 'react-aria/useDialog';
import { StyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface SpectrumDialogProps extends AriaDialogProps, StyleProps {
    /** The contents of the Dialog. */
    children: ReactNode;
    /** The size of the Dialog. Only applies to "modal" type Dialogs. */
    size?: 'S' | 'M' | 'L';
    /** Whether the Dialog is dismissable. See the [examples](#examples) for more details. */
    isDismissable?: boolean;
    /** Handler that is called when the 'x' button of a dismissable Dialog is clicked. */
    onDismiss?: () => void;
}
/**
 * Dialogs are windows containing contextual information, tasks, or workflows that appear over the
 * user interface. Depending on the kind of Dialog, further interactions may be blocked until the
 * Dialog is acknowledged.
 */
export declare const Dialog: React.ForwardRefExoticComponent<SpectrumDialogProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLElement>>>;
