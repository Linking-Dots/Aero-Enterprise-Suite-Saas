import { Node } from '@react-types/shared';
import { ReactNode } from 'react';
interface SpectrumStepListItemProps<T> {
    item: Node<T>;
    isDisabled?: boolean;
    isEmphasized?: boolean;
    isReadOnly?: boolean;
}
export declare function StepListItem<T>(props: SpectrumStepListItemProps<T>): ReactNode;
export {};
