import { AriaSearchAutocompleteProps } from 'react-aria/private/autocomplete/useSearchAutocomplete';
import { AsyncLoadable, DimensionValue, FocusableRef, LoadingState, SpectrumFieldValidation, SpectrumLabelableProps, SpectrumTextInputBase, StyleProps } from '@react-types/shared';
import { MenuTriggerAction } from 'react-stately/useComboBoxState';
import React, { ReactElement } from 'react';
export interface SpectrumSearchAutocompleteProps<T> extends SpectrumTextInputBase, Omit<AriaSearchAutocompleteProps<T>, 'menuTrigger' | 'isInvalid' | 'validationState' | 'validate'>, SpectrumFieldValidation<string>, SpectrumLabelableProps, StyleProps, Omit<AsyncLoadable, 'isLoading'> {
    /**
     * The interaction required to display the SearchAutocomplete menu. Note that this prop has no
     * effect on the mobile SearchAutocomplete experience.
     *
     * @default 'input'
     */
    menuTrigger?: MenuTriggerAction;
    /** Whether the SearchAutocomplete should be displayed with a quiet style. */
    isQuiet?: boolean;
    /**
     * Alignment of the menu relative to the input target.
     *
     * @default 'start'
     */
    align?: 'start' | 'end';
    /**
     * Direction the menu will render relative to the SearchAutocomplete.
     *
     * @default 'bottom'
     */
    direction?: 'bottom' | 'top';
    /**
     * The current loading state of the SearchAutocomplete. Determines whether or not the progress
     * circle should be shown.
     */
    loadingState?: LoadingState;
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
    onLoadMore?: () => void;
    /** An icon to display at the start of the input. */
    icon?: ReactElement | null;
}
/**
 * A SearchAutocomplete is a searchfield that supports a dynamic list of suggestions.
 */
declare let ForwardSearchAutocomplete: <T>(props: SpectrumSearchAutocompleteProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export { ForwardSearchAutocomplete as SearchAutocomplete };
