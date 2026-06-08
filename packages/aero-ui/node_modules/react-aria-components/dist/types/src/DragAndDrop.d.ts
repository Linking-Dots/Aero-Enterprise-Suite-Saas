import type { DropIndicatorProps as AriaDropIndicatorProps } from 'react-aria/useDroppableCollection';
import type { ClassNameOrFunction, RenderProps } from './utils';
import type { DragAndDropHooks } from './useDragAndDrop';
import type { DraggableCollectionState } from 'react-stately/useDraggableCollectionState';
import type { DroppableCollectionState } from 'react-stately/useDroppableCollectionState';
import type { ItemDropTarget, Key } from '@react-types/shared';
import type { MultipleSelectionManager } from 'react-stately/useMultipleSelectionState';
import React, { ForwardedRef, ReactNode } from 'react';
export interface DragAndDropContextValue {
    dragAndDropHooks?: DragAndDropHooks;
    dragState?: DraggableCollectionState;
    dropState?: DroppableCollectionState;
}
export declare const DragAndDropContext: React.Context<DragAndDropContextValue>;
export declare const DropIndicatorContext: React.Context<DropIndicatorContextValue | null>;
export interface DropIndicatorRenderProps {
    /**
     * Whether the drop indicator is currently the active drop target.
     *
     * @selector [data-drop-target]
     */
    isDropTarget: boolean;
}
export interface DropIndicatorProps extends Omit<AriaDropIndicatorProps, 'activateButtonRef'>, RenderProps<DropIndicatorRenderProps> {
    /**
     * The CSS [className](https://developer.mozilla.org/en-US/docs/Web/API/Element/className) for the
     * element. A function may be provided to compute the class based on component state.
     *
     * @default 'react-aria-DropIndicator'
     */
    className?: ClassNameOrFunction<DropIndicatorRenderProps>;
}
interface DropIndicatorContextValue {
    render: (props: DropIndicatorProps, ref: ForwardedRef<HTMLElement>) => ReactNode;
}
/**
 * A DropIndicator is rendered between items in a collection to indicate where dropped data will be
 * inserted.
 */
export declare const DropIndicator: React.ForwardRefExoticComponent<DropIndicatorProps & React.RefAttributes<HTMLElement>>;
type RenderDropIndicatorRetValue = ((target: ItemDropTarget) => ReactNode | undefined) | undefined;
export declare function useRenderDropIndicator(dragAndDropHooks?: DragAndDropHooks, dropState?: DroppableCollectionState): RenderDropIndicatorRetValue;
export declare function useDndPersistedKeys(selectionManager: MultipleSelectionManager, dragAndDropHooks?: DragAndDropHooks, dropState?: DroppableCollectionState): Set<Key>;
export {};
