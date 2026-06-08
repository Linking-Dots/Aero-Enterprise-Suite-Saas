import { AriaAutocompleteProps } from 'react-aria/useAutocomplete';
import { AriaLabelingProps, DOMProps, FocusableElement, FocusEvents, KeyboardEvents, Node, ValueBase } from '@react-types/shared';
import { AriaTextFieldProps } from 'react-aria/useTextField';
import { AutocompleteState } from 'react-stately/private/autocomplete/useAutocompleteState';
import { ContextValue, SlotProps, SlottedContextValue } from './utils';
import React, { JSX } from 'react';
export interface AutocompleteProps<T = object> extends AriaAutocompleteProps<T>, SlotProps {
}
export declare const AutocompleteContext: React.Context<SlottedContextValue<Partial<AutocompleteProps<any>>>>;
export declare const AutocompleteStateContext: React.Context<AutocompleteState | null>;
export interface SelectableCollectionContextValue<T> extends DOMProps, AriaLabelingProps {
    filter?: (nodeTextValue: string, node: Node<T>) => boolean;
    /** Whether the collection items should use virtual focus instead of being focused directly. */
    shouldUseVirtualFocus?: boolean;
    /** Whether typeahead is disabled. */
    disallowTypeAhead?: boolean;
}
interface FieldInputContextValue<T = FocusableElement> extends DOMProps, FocusEvents<T>, KeyboardEvents, Pick<ValueBase<string>, 'onChange' | 'value'>, Pick<AriaTextFieldProps, 'enterKeyHint' | 'aria-controls' | 'aria-autocomplete' | 'aria-activedescendant' | 'spellCheck' | 'autoCorrect' | 'autoComplete'> {
}
export declare const SelectableCollectionContext: React.Context<ContextValue<SelectableCollectionContextValue<any>, HTMLElement>>;
export declare const FieldInputContext: React.Context<ContextValue<FieldInputContextValue<FocusableElement>, FocusableElement>>;
/**
 * An autocomplete allows users to search or filter a list of suggestions.
 */
export declare function Autocomplete<T>(props: AutocompleteProps<T>): JSX.Element;
export {};
