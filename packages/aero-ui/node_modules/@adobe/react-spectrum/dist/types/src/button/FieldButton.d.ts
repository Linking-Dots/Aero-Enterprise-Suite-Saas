import { ButtonProps } from 'react-aria/useButton';
import { DOMProps, StyleProps } from '@react-types/shared';
import React from 'react';
interface FieldButtonProps extends ButtonProps, DOMProps, StyleProps {
    isQuiet?: boolean;
    isActive?: boolean;
    validationState?: 'valid' | 'invalid';
    isInvalid?: boolean;
    focusRingClass?: string;
}
export declare const FieldButton: React.ForwardRefExoticComponent<FieldButtonProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLElement, HTMLElement>>>;
export {};
