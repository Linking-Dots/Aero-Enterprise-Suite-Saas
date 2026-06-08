import { TableLayout as BaseTableLayout, TableLayoutProps } from 'react-stately/useVirtualizerState';
import { LayoutOptionsDelegate } from './Virtualizer';
export declare class TableLayout<T, O extends TableLayoutProps = TableLayoutProps> extends BaseTableLayout<T, O> implements LayoutOptionsDelegate<TableLayoutProps> {
    useLayoutOptions(): TableLayoutProps;
}
