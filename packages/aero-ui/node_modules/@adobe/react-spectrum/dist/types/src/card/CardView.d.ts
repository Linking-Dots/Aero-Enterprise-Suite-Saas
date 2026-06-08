import { DOMRef } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumCardViewProps } from './types';
/**
 * TODO: Add description of component here.
 */
export declare const CardView: <T>(props: SpectrumCardViewProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
