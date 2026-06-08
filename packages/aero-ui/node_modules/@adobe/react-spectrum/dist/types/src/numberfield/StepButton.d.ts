import { AriaButtonProps } from 'react-aria/useButton';
import { FocusableRef } from '@react-types/shared';
import React, { ReactElement } from 'react';
interface StepButtonProps extends AriaButtonProps {
    isQuiet?: boolean;
    direction: 'up' | 'down';
}
/**
 * Buttons for NumberField.
 */
export declare const StepButton: (props: StepButtonProps & {
    ref?: FocusableRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export {};
