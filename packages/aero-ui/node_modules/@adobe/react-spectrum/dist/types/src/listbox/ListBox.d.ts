import { DOMRef } from '@react-types/shared';
import { SpectrumListBoxProps } from './ListBoxBase';
import React, { ReactElement } from 'react';
/**
 * A list of options that can allow selection of one or more.
 */
export declare const ListBox: <T>(props: SpectrumListBoxProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
