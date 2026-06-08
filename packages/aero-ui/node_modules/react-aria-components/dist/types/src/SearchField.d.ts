import { AriaSearchFieldProps } from 'react-aria/useSearchField';
import { ClassNameOrFunction, ContextValue, RACValidation, RenderProps, SlotProps } from './utils';
import { GlobalDOMAttributes } from '@react-types/shared';
import React from 'react';
import { SearchFieldState } from 'react-stately/useSearchFieldState';
export interface SearchFieldRenderProps {
    /**
     * Whether the search field is empty.
     *
     * @selector [data-empty]
     */
    isEmpty: boolean;
    /**
     * Whether the search field is disabled.
     *
     * @selector [data-disabled]
     */
    isDisabled: boolean;
    /**
     * Whether the search field is invalid.
     *
     * @selector [data-invalid]
     */
    isInvalid: boolean;
    /**
     * Whether the search field is read only.
     *
     * @selector [data-readonly]
     */
    isReadOnly: boolean;
    /**
     * Whether the search field is required.
     *
     * @selector [data-required]
     */
    isRequired: boolean;
    /**
     * State of the search field.
     */
    state: SearchFieldState;
}
export interface SearchFieldProps extends Omit<AriaSearchFieldProps, 'label' | 'placeholder' | 'description' | 'errorMessage' | 'validationState' | 'validationBehavior'>, RACValidation, RenderProps<SearchFieldRenderProps>, SlotProps, GlobalDOMAttributes<HTMLDivElement> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-SearchField'
     */
    className?: ClassNameOrFunction<SearchFieldRenderProps>;
}
export declare const SearchFieldContext: React.Context<ContextValue<SearchFieldProps, HTMLDivElement>>;
/**
 * A search field allows a user to enter and clear a search query.
 */
export declare const SearchField: (props: SearchFieldProps & React.RefAttributes<HTMLDivElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
