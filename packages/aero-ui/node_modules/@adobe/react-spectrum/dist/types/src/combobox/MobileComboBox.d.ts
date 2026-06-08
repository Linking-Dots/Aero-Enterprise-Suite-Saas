import { AriaButtonProps } from 'react-aria/useButton';
import { FocusableRefValue, ValidationState } from '@react-types/shared';
import React, { ReactNode } from 'react';
import { SpectrumComboBoxProps } from './ComboBox';
export declare const MobileComboBox: React.ForwardRefExoticComponent<SpectrumComboBoxProps<any> & React.RefAttributes<FocusableRefValue<HTMLElement, HTMLElement>>>;
interface ComboBoxButtonProps extends AriaButtonProps {
    isQuiet?: boolean;
    isDisabled?: boolean;
    isPlaceholder?: boolean;
    validationState?: ValidationState;
    children?: ReactNode;
    style?: React.CSSProperties;
    className?: string;
}
export declare const ComboBoxButton: React.ForwardRefExoticComponent<ComboBoxButtonProps & React.RefAttributes<HTMLDivElement>>;
export {};
