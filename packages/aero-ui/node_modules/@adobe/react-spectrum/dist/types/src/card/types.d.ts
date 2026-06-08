import { AriaLabelingProps, AsyncLoadable, Collection, CollectionBase, Direction, DOMProps, KeyboardDelegate, LoadingState, MultipleSelection, Node, Orientation, StyleProps } from '@react-types/shared';
import { Layout } from 'react-stately/useVirtualizerState';
import { ReactNode } from 'react';
import { Scale } from '../provider/types';
interface AriaCardProps extends AriaLabelingProps {
}
export interface SpectrumCardProps extends AriaCardProps, StyleProps, DOMProps {
    children: ReactNode;
    isQuiet?: boolean;
    layout?: 'grid' | 'waterfall' | 'gallery';
    orientation?: Orientation;
}
interface LayoutOptions {
    cardOrientation?: Orientation;
    collator?: Intl.Collator;
    scale?: Scale;
}
interface CardViewLayout<T> extends Layout<Node<T>>, KeyboardDelegate {
    collection: Collection<Node<T>>;
    disabledKeys: any;
    isLoading: boolean;
    direction: Direction;
    layoutType: string;
    margin?: number;
}
export interface CardViewLayoutConstructor<T> {
    new (options?: LayoutOptions): CardViewLayout<T>;
}
interface CardViewProps<T> extends CollectionBase<T>, MultipleSelection, Omit<AsyncLoadable, 'isLoading'> {
    layout: CardViewLayoutConstructor<T> | CardViewLayout<T>;
    cardOrientation?: Orientation;
    isQuiet?: boolean;
    renderEmptyState?: () => ReactNode;
    loadingState?: LoadingState;
}
export interface AriaCardViewProps<T> extends CardViewProps<T>, DOMProps, AriaLabelingProps {
}
export interface SpectrumCardViewProps<T> extends AriaCardViewProps<T>, StyleProps {
}
export {};
