import { AriaButtonProps } from 'react-aria/useButton';
import { DOMProps, StyleProps } from '@react-types/shared';
import React, { ElementType } from 'react';
interface ClearButtonProps<T extends ElementType = 'button'> extends AriaButtonProps<T>, DOMProps, StyleProps {
    focusClassName?: string;
    variant?: 'overBackground';
    excludeFromTabOrder?: boolean;
    preventFocus?: boolean;
    inset?: boolean;
}
export declare const ClearButton: React.ForwardRefExoticComponent<ClearButtonProps<"button"> & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLButtonElement, HTMLButtonElement>>>;
export {};
