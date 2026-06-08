import { AriaButtonProps } from 'react-aria/useButton';
import { FocusableRef, StyleProps } from '@react-types/shared';
import React, { ElementType, ReactElement } from 'react';
/** @deprecated */
type LegacyButtonVariant = 'cta' | 'overBackground';
export interface SpectrumButtonProps<T extends ElementType = 'button'> extends Omit<AriaButtonProps<T>, 'onClick'>, StyleProps {
    /** The [visual style](https://spectrum.adobe.com/page/button/#Options) of the button. */
    variant: 'accent' | 'primary' | 'secondary' | 'negative' | LegacyButtonVariant;
    /** The background style of the button. */
    style?: 'fill' | 'outline';
    /** The static color style to apply. Useful when the button appears over a color background. */
    staticColor?: 'white' | 'black';
    /**
     * Whether to disable events immediately and display a loading spinner after a 1 second delay.
     */
    isPending?: boolean;
    /**
     * Whether the button should be displayed with a quiet style.
     *
     * @deprecated
     */
    isQuiet?: boolean;
}
/**
 * Buttons allow users to perform an action or to navigate to another page.
 * They have multiple styles for various needs, and are ideal for calling attention to
 * where a user needs to do something in order to move forward in a flow.
 */
export declare const Button: <T extends ElementType = "button">(props: SpectrumButtonProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export {};
