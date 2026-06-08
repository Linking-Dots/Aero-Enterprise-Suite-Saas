import { AriaComboBoxProps } from 'react-aria/useComboBox';
import { AsyncLoadable, DimensionValue, FocusableRef, LoadingState, SingleSelection, SpectrumFieldValidation, SpectrumLabelableProps, SpectrumTextInputBase, StyleProps } from '@react-types/shared';
import { ComboBoxValidationValue, MenuTriggerAction } from 'react-stately/useComboBoxState';
import React, { ReactElement } from 'react';
export interface SpectrumComboBoxProps<T> extends SpectrumTextInputBase, Omit<AriaComboBoxProps<T>, 'menuTrigger' | 'isInvalid' | 'validationState' | 'selectionMode' | 'selectedKey' | 'defaultSelectedKey' | 'onSelectionChange' | 'value' | 'defaultValue' | 'onChange'>, Omit<SingleSelection, 'disallowEmptySelection'>, SpectrumFieldValidation<ComboBoxValidationValue>, SpectrumLabelableProps, StyleProps, Omit<AsyncLoadable, 'isLoading'> {
    /**
     * The interaction required to display the ComboBox menu. Note that this prop has no effect on the
     * mobile ComboBox experience.
     *
     * @default 'input'
     */
    menuTrigger?: MenuTriggerAction;
    /** Whether the ComboBox should be displayed with a quiet style. */
    isQuiet?: boolean;
    /**
     * Alignment of the menu relative to the input target.
     *
     * @default 'start'
     */
    align?: 'start' | 'end';
    /**
     * Direction the menu will render relative to the ComboBox.
     *
     * @default 'bottom'
     */
    direction?: 'bottom' | 'top';
    /**
     * The current loading state of the ComboBox. Determines whether or not the progress circle should
     * be shown.
     */
    loadingState?: LoadingState;
    /**
     * Whether the menu should automatically flip direction when space is limited.
     *
     * @default true
     */
    shouldFlip?: boolean;
    /**
     * Width of the menu. By default, matches width of the combobox. Note that the minimum width of
     * the dropdown is always equal to the combobox's width.
     */
    menuWidth?: DimensionValue;
    /**
     * Whether the text or key of the selected item is submitted as part of an HTML form. When
     * `allowsCustomValue` is `true`, this option does not apply and the text is always submitted.
     *
     * @default 'text'
     */
    formValue?: 'text' | 'key';
}
/**
 * ComboBoxes combine a text entry with a picker menu, allowing users to filter longer lists to only
 * the selections matching a query.
 */
export declare const ComboBox: <T>(props: SpectrumComboBoxProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
