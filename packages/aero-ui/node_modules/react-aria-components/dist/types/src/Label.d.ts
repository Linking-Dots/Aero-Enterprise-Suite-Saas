import { ContextValue, DOMRenderProps } from './utils';
import React, { LabelHTMLAttributes } from 'react';
export interface LabelProps extends LabelHTMLAttributes<HTMLLabelElement>, DOMRenderProps<'label', undefined> {
    elementType?: string;
}
export declare const LabelContext: React.Context<ContextValue<LabelProps, HTMLLabelElement>>;
export declare const Label: (props: LabelProps & React.RefAttributes<HTMLLabelElement>) => React.ReactElement<unknown, string | React.JSXElementConstructor<any>> | null;
