import { SpectrumCardProps } from './types';
import { Node } from '@react-types/shared';
import React, { HTMLAttributes } from 'react';
interface CardBaseProps<T> extends SpectrumCardProps {
    articleProps?: HTMLAttributes<HTMLElement>;
    item?: Node<T>;
}
/**
 * TODO: Add description of component here.
 */
export declare const CardBase: React.ForwardRefExoticComponent<CardBaseProps<object> & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
export {};
