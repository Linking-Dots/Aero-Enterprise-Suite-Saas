import { Direction, DropTarget, DropTargetDelegate, Key, Node } from '@react-types/shared';
interface TreeCollection<T> extends Iterable<Node<T>> {
    getItem(key: Key): Node<T> | null;
    getChildren?(key: Key): Iterable<Node<T>>;
    getKeyAfter(key: Key): Key | null;
    getKeyBefore(key: Key): Key | null;
}
interface TreeState<T> {
    collection: TreeCollection<T>;
    expandedKeys: Set<Key>;
}
export declare class TreeDropTargetDelegate<T> {
    private delegate;
    private state;
    private direction;
    private pointerTracking;
    setup(delegate: DropTargetDelegate, state: TreeState<T>, direction: Direction): void;
    getDropTargetFromPoint(x: number, y: number, isValidDropTarget: (target: DropTarget) => boolean): DropTarget | null;
    private resolveDropTarget;
    private getPotentialTargets;
    private selectTarget;
}
export {};
