import { FocusableRef } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumSearchAutocompleteProps } from './SearchAutocomplete';
export declare let MobileSearchAutocomplete: <T>(props: SpectrumSearchAutocompleteProps<T> & {
    ref?: FocusableRef<HTMLElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
