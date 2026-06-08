import { DOMRef } from '@react-types/shared';
import React, { ReactElement } from 'react';
import { SpectrumTableProps } from './TableView';
interface TableProps<T> extends Omit<SpectrumTableProps<T>, 'UNSTABLE_allowsExpandableRows'> {
}
export declare const TableViewWithoutExpanding: <T>(props: TableProps<T> & {
    ref?: DOMRef<HTMLDivElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
export {};
