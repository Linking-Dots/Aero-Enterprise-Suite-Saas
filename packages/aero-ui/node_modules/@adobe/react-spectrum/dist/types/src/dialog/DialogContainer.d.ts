import { JSX, ReactNode } from 'react';
export interface SpectrumDialogContainerProps {
    /** The Dialog to display, if any. */
    children: ReactNode;
    /** Handler that is called when the 'x' button of a dismissable Dialog is clicked. */
    onDismiss: () => void;
    /**
     * The type of Dialog that should be rendered. See the visual options below for examples of each.
     *
     * @default 'modal'
     */
    type?: 'modal' | 'fullscreen' | 'fullscreenTakeover';
    /**
     * Whether the Dialog is dismissable. See the [Dialog docs](Dialog.html#dismissable-dialogs) for
     * more details.
     */
    isDismissable?: boolean;
    /** Whether pressing the escape key to close the dialog should be disabled. */
    isKeyboardDismissDisabled?: boolean;
}
/**
 * A DialogContainer accepts a single Dialog as a child, and manages showing and hiding
 * it in a modal. Useful in cases where there is no trigger element
 * or when the trigger unmounts while the dialog is open.
 */
export declare function DialogContainer(props: SpectrumDialogContainerProps): JSX.Element;
