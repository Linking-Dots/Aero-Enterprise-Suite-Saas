import { AriaTagProps } from 'react-aria/useTagGroup';
import type { ListState } from 'react-stately/useListState';
import { ReactNode } from 'react';
export interface SpectrumTagProps<T> extends AriaTagProps<T> {
    state: ListState<T>;
}
export declare function Tag<T>(props: SpectrumTagProps<T>): ReactNode;
