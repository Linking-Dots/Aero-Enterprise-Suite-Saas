import { DropIndicatorProps as AriaDropIndicatorProps, DropIndicatorAria, DroppableCollectionOptions, DroppableCollectionResult, DroppableItemOptions, DroppableItemResult } from 'react-aria/useDroppableCollection';
import { DraggableCollectionOptions, DraggableItemProps, DraggableItemResult, DragPreview } from 'react-aria/useDraggableCollection';
import { DraggableCollectionProps, DroppableCollectionProps, Key, RefObject } from '@react-types/shared';
import { DraggableCollectionState, DraggableCollectionStateOptions } from 'react-stately/useDraggableCollectionState';
import { DragItem, DropTarget, DropTargetDelegate } from '@react-types/shared';
import { DroppableCollectionState, DroppableCollectionStateOptions } from 'react-stately/useDroppableCollectionState';
import { JSX } from 'react';
import { ListDropTargetDelegate } from 'react-aria/ListDropTargetDelegate';
export { DropIndicator, DropIndicatorContext, DragAndDropContext } from './DragAndDrop';
export type { DropIndicatorProps, DropIndicatorRenderProps } from './DragAndDrop';
interface DraggableCollectionStateOpts<T = object> extends Omit<DraggableCollectionStateOptions<T>, 'getItems'> {
}
interface DragHooks<T = object> {
    useDraggableCollectionState?: (props: DraggableCollectionStateOpts<T>) => DraggableCollectionState;
    useDraggableCollection?: (props: DraggableCollectionOptions, state: DraggableCollectionState, ref: RefObject<HTMLElement | null>) => void;
    useDraggableItem?: (props: DraggableItemProps, state: DraggableCollectionState) => DraggableItemResult;
    DragPreview?: typeof DragPreview;
    renderDragPreview?: (items: DragItem[]) => JSX.Element | {
        element: JSX.Element;
        x: number;
        y: number;
    };
    isVirtualDragging?: () => boolean;
}
interface DropHooks {
    useDroppableCollectionState?: (props: DroppableCollectionStateOptions) => DroppableCollectionState;
    useDroppableCollection?: (props: DroppableCollectionOptions, state: DroppableCollectionState, ref: RefObject<HTMLElement | null>) => DroppableCollectionResult;
    useDroppableItem?: (options: DroppableItemOptions, state: DroppableCollectionState, ref: RefObject<HTMLElement | null>) => DroppableItemResult;
    useDropIndicator?: (props: AriaDropIndicatorProps, state: DroppableCollectionState, ref: RefObject<HTMLElement | null>) => DropIndicatorAria;
    renderDropIndicator?: (target: DropTarget) => JSX.Element;
    dropTargetDelegate?: DropTargetDelegate;
    ListDropTargetDelegate: typeof ListDropTargetDelegate;
}
export type DragAndDropHooks<T = object> = DragHooks<T> & DropHooks;
export interface DragAndDrop<T = object> {
    /** Drag and drop hooks for the collection element. */
    dragAndDropHooks: DragAndDropHooks<T>;
}
export interface DragAndDropOptions<T = object> extends Omit<DraggableCollectionProps, 'preview' | 'getItems'>, DroppableCollectionProps {
    /**
     * A function that returns the items being dragged. If not specified, we assume that the
     * collection is not draggable.
     *
     * @default () => []
     */
    getItems?: (keys: Set<Key>, items: T[]) => DragItem[];
    /**
     * A function that renders a drag preview, which is shown under the user's cursor while dragging.
     * By default, a copy of the dragged element is rendered.
     */
    renderDragPreview?: (items: DragItem[]) => JSX.Element | {
        element: JSX.Element;
        x: number;
        y: number;
    };
    /**
     * A function that renders a drop indicator element between two items in a collection.
     * This should render a `<DropIndicator>` element. If this function is not provided, a
     * default DropIndicator is provided.
     */
    renderDropIndicator?: (target: DropTarget) => JSX.Element;
    /**
     * A custom delegate object that provides drop targets for pointer coordinates within the
     * collection.
     */
    dropTargetDelegate?: DropTargetDelegate;
    /** Whether the drag and drop events should be disabled. */
    isDisabled?: boolean;
}
/**
 * Provides the hooks required to enable drag and drop behavior for a drag and drop compatible
 * collection component.
 */
export declare function useDragAndDrop<T = object>(options: DragAndDropOptions<T>): DragAndDrop<T>;
