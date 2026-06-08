import { Alignment, AsyncLoadable, DimensionValue, DOMRef, SingleSelection, SpectrumLabelableProps, StyleProps } from '@react-types/shared';
import { AriaSelectProps } from 'react-aria/useSelect';
import React, { ReactElement } from 'react';
export interface SpectrumPickerProps<T> extends Omit<AriaSelectProps<T>, 'selectionMode' | 'selectedKey' | 'defaultSelectedKey' | 'onSelectionChange' | 'value' | 'defaultValue' | 'onChange' | 'allowsEmptyCollection'>, Omit<SingleSelection, 'disallowEmptySelection'>, AsyncLoadable, SpectrumLabelableProps, StyleProps {
    /** Whether the textfield should be displayed with a quiet style. */
    isQuiet?: boolean;
    /**
     * Alignment of the menu relative to the input target.
     *
     * @default 'start'
     */
    align?: Alignment;
    /**
     * Direction the menu will render relative to the Picker.
     *
     * @default 'bottom'
     */
    direction?: 'bottom' | 'top';
    /**
     * Whether the menu should automatically flip direction when space is limited.
     *
     * @default true
     */
    shouldFlip?: boolean;
    /**
     * Width of the menu. By default, matches width of the trigger. Note that the minimum width of the
     * dropdown is always equal to the trigger's width.
     */
    menuWidth?: DimensionValue;
    /** Whether the element should receive focus on render. */
    autoFocus?: boolean;
}
/**
 * Pickers allow users to choose a single option from a collapsible list of options when space is
 * limited.
 */
export declare const Picker: <T>(props: SpectrumPickerProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
