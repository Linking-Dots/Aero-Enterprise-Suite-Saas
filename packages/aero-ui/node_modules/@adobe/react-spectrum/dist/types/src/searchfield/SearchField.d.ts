import { AriaSearchFieldProps } from 'react-aria/useSearchField';
import React, { ReactElement, Ref } from 'react';
import { SpectrumTextFieldProps, TextFieldRef } from '../textfield/TextField';
import { SpectrumTextInputBase } from '@react-types/shared';
export interface SpectrumSearchFieldProps extends SpectrumTextInputBase, Omit<AriaSearchFieldProps, 'isInvalid' | 'validationState'>, SpectrumTextFieldProps {
}
/**
 * A SearchField is a text field designed for searches.
 */
export declare const SearchField: (props: SpectrumSearchFieldProps & {
    ref?: Ref<TextFieldRef<HTMLInputElement>> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
