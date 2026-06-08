import { OverlayTriggerProps } from 'react-stately/useOverlayTriggerState';
import { PositionProps } from 'react-aria/useOverlayPosition';
import React, { JSX, ReactElement, RefObject } from 'react';
export type SpectrumDialogClose = (close: () => void) => ReactElement;
export interface SpectrumDialogTriggerProps extends OverlayTriggerProps, PositionProps {
    /**
     * The Dialog and its trigger element. See the DialogTrigger [Content section](#content) for more
     * information on what to provide as children.
     */
    children: [ReactElement, SpectrumDialogClose | ReactElement];
    /**
     * The type of Dialog that should be rendered. See the DialogTrigger [types
     * section](#dialog-types) for an explanation on each.
     *
     * @default 'modal'
     */
    type?: 'modal' | 'popover' | 'tray' | 'fullscreen' | 'fullscreenTakeover';
    /**
     * The type of Dialog that should be rendered when on a mobile device. See DialogTrigger [types
     * section](#dialog-types) for an explanation on each.
     */
    mobileType?: 'modal' | 'tray' | 'fullscreen' | 'fullscreenTakeover';
    /**
     * Whether a popover type Dialog's arrow should be hidden.
     */
    hideArrow?: boolean;
    /**
     * The ref of the element the Dialog should visually attach itself to. Defaults to the trigger
     * button if not defined.
     */
    targetRef?: RefObject<HTMLElement | null>;
    /** Whether a modal type Dialog should be dismissable. */
    isDismissable?: boolean;
    /** Whether pressing the escape key to close the dialog should be disabled. */
    isKeyboardDismissDisabled?: boolean;
}
declare function DialogTrigger(props: SpectrumDialogTriggerProps): JSX.Element;
declare namespace DialogTrigger {
    var getCollectionNode: (props: SpectrumDialogTriggerProps) => Generator<{
        element: string | number | bigint | Iterable<React.ReactNode> | Promise<string | number | bigint | boolean | Iterable<React.ReactNode> | ReactElement<unknown, string | React.JSXElementConstructor<any>> | React.ReactPortal | null | undefined> | ReactElement<unknown, string | React.JSXElementConstructor<any>> | React.ReactPortal;
        wrapper: (element: any) => JSX.Element;
    }, void, unknown>;
}
/**
 * DialogTrigger serves as a wrapper around a Dialog and its associated trigger, linking the
 * Dialog's open state with the trigger's press state. Additionally, it allows you to customize the
 * type and positioning of the Dialog.
 */
declare let _DialogTrigger: (props: SpectrumDialogTriggerProps) => JSX.Element;
export { _DialogTrigger as DialogTrigger };
