import { AriaPopoverProps } from 'react-aria/usePopover';
import { StyleProps } from '@react-types/shared';
import { FocusWithinProps } from 'react-aria/useFocusWithin';
import { OverlayTriggerState } from 'react-stately/useOverlayTriggerState';
import React, { ReactNode } from 'react';
interface PopoverProps extends Omit<AriaPopoverProps, 'popoverRef' | 'maxHeight'>, FocusWithinProps, StyleProps {
    children: ReactNode;
    hideArrow?: boolean;
    state: OverlayTriggerState;
    shouldContainFocus?: boolean;
    onEntering?: () => void;
    onEnter?: () => void;
    onEntered?: () => void;
    onExiting?: () => void;
    onExited?: () => void;
    onExit?: () => void;
    container?: HTMLElement;
    disableFocusManagement?: boolean;
    enableBothDismissButtons?: boolean;
    onDismissButtonPress?: () => void;
}
export declare const Popover: React.ForwardRefExoticComponent<PopoverProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
/**
 * More explanation on popover tips. - I tried changing the calculation of the popover placement in
 * an effort to get it squarely onto the pixel grid. This did not work because the problem was in
 * the svg partial pixel end of the path in the popover right and popover bottom. - I tried creating
 * an extra 'bandaid' path that matched the background color and would overlap the popover border.
 * This didn't work because the border on the svg triangle didn't extend all the way to match nicely
 * with the popover border. - I tried getting the client bounding box and setting the svg to that
 * partial pixel value This didn't work because again the issue was inside the svg - I didn't try
 * drawing the svg backwards This could still be tried - I tried changing the calculation of the
 * popover placement AND the svg height/width so that they were all rounded This seems to have done
 * the trick.
 */
