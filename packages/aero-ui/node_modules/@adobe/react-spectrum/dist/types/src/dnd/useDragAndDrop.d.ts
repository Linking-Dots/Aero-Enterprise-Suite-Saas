import { DraggableCollectionOptions, DraggableItemProps, DraggableItemResult, DragPreview } from 'react-aria/useDraggableCollection';
import { DraggableCollectionProps, DragItem, DroppableCollectionProps, Key, RefObject } from '@react-types/shared';
import { DraggableCollectionState, DraggableCollectionStateOptions } from 'react-stately/useDraggableCollectionState';
import { DropIndicatorAria, DropIndicatorProps, DroppableCollectionOptions, DroppableCollectionResult, DroppableItemOptions, DroppableItemResult } from 'react-aria/useDroppableCollection';
import { DroppableCollectionState, DroppableCollectionStateOptions } from 'react-stately/useDroppableCollectionState';
import { JSX } from 'react';
interface DraggableCollectionStateOpts<T> extends Omit<DraggableCollectionStateOptions<T>, 'getItems'> {
}
interface DragHooks<T = object> {
    useDraggableCollectionState?: (props: DraggableCollectionStateOpts<T>) => DraggableCollectionState;
    useDraggableCollection?: (props: DraggableCollectionOptions, state: DraggableCollectionState, ref: RefObject<HTMLElement | null>) => void;
    useDraggableItem?: (props: DraggableItemProps, state: DraggableCollectionState) => DraggableItemResult;
    DragPreview?: typeof DragPreview;
}
interface DropHooks {
    useDroppableCollectionState?: (props: DroppableCollectionStateOptions) => DroppableCollectionState;
    useDroppableCollection?: (props: DroppableCollectionOptions, state: DroppableCollectionState, ref: RefObject<HTMLElement | null>) => DroppableCollectionResult;
    useDroppableItem?: (options: DroppableItemOptions, state: DroppableCollectionState, ref: RefObject<HTMLElement | null>) => DroppableItemResult;
    useDropIndicator?: (props: DropIndicatorProps, state: DroppableCollectionState, ref: RefObject<HTMLElement | null>) => DropIndicatorAria;
}
export interface DragAndDropHooks<T = object> {
    /** Drag and drop hooks for the collection element. */
    dragAndDropHooks: DragHooks<T> & DropHooks & {
        isVirtualDragging?: () => boolean;
        renderPreview?: (keys: Set<Key>, draggedKey: Key) => JSX.Element;
    };
}
export interface DragAndDropOptions<T = object> extends Omit<DraggableCollectionProps, 'preview' | 'getItems'>, Omit<DroppableCollectionProps, 'onMove'> {
    /**
     * A function that returns the items being dragged. If not specified, we assume that the
     * collection is not draggable.
     *
     * @default () => []
     */
    getItems?: (keys: Set<Key>, items: T[]) => DragItem[];
    /**
     * Provide a custom drag preview. `draggedKey` represents the key of the item the user actually
     * dragged.
     */
    renderPreview?: (keys: Set<Key>, draggedKey: Key) => JSX.Element;
}
/**
 * Provides the hooks required to enable drag and drop behavior for a drag and drop compatible React
 * Spectrum component.
 */
export declare function useDragAndDrop<T = object>(options: DragAndDropOptions<T>): DragAndDropHooks;
export {};
