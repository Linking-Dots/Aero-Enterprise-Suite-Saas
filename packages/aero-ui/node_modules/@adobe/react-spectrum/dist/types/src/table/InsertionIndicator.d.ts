import { FocusableElement, ItemDropTarget } from '@react-types/shared';
import { DOMAttributes, HTMLAttributes, ReactNode } from 'react';
interface InsertionIndicatorProps {
    target: ItemDropTarget;
    rowProps: HTMLAttributes<HTMLElement> & DOMAttributes<FocusableElement>;
}
export declare function InsertionIndicator(props: InsertionIndicatorProps): ReactNode | null;
export {};
