import { DOMRef } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumTableProps } from './TableView';
export interface TreeGridTableProps<T> extends Omit<SpectrumTableProps<T>, 'UNSTABLE_allowsExpandableRows'> {
}
export declare const TreeGridTableView: <T>(props: TreeGridTableProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
