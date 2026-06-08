import { AriaActionGroupProps } from 'react-aria/private/actiongroup/useActionGroup';
import { DOMRef, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumActionGroupProps<T> extends AriaActionGroupProps<T>, StyleProps {
    /**
     * Whether the ActionButtons should be displayed with a [emphasized
     * style](https://spectrum.adobe.com/page/action-button/#Emphasis).
     */
    isEmphasized?: boolean;
    /**
     * Sets the amount of space between buttons.
     *
     * @default 'regular'
     */
    density?: 'compact' | 'regular';
    /** Whether the ActionButtons should be justified in their container. */
    isJustified?: boolean;
    /**
     * Whether ActionButtons should use the [quiet
     * style](https://spectrum.adobe.com/page/action-button/#Quiet).
     */
    isQuiet?: boolean;
    /** The static color style to apply. Useful when the ActionGroup appears over a color background. */
    staticColor?: 'white' | 'black';
    /**
     * Defines the behavior of the ActionGroup when the buttons do not fit in the available space.
     * When set to 'wrap', the items wrap to form a new line. When set to 'collapse', the items that
     * do not fit are collapsed into a dropdown menu.
     *
     * @default 'wrap'
     */
    overflowMode?: 'wrap' | 'collapse';
    /**
     * Defines when the text within the buttons should be hidden and only the icon should be shown.
     * When set to 'hide', the text is always shown in a tooltip. When set to 'collapse', the text is
     * visible if space is available, and hidden when space is limited. The text is always visible
     * when the item is collapsed into a menu.
     *
     * @default 'show'
     */
    buttonLabelBehavior?: 'show' | 'collapse' | 'hide';
    /** The icon displayed in the dropdown menu button when a selectable ActionGroup is collapsed. */
    summaryIcon?: ReactElement;
}
/**
 * An ActionGroup is a grouping of ActionButtons that are related to one another.
 */
export declare const ActionGroup: <T>(props: SpectrumActionGroupProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
