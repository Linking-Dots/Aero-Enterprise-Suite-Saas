import { ColumnProps } from 'react-stately/useTableState';
export interface SpectrumColumnProps<T> extends ColumnProps<T> {
    /**
     * The alignment of the column's contents relative to its allotted width.
     *
     * @default 'start'
     */
    align?: 'start' | 'center' | 'end';
    /** Whether the column should render a divider between it and the next column. */
    showDivider?: boolean;
    /**
     * Whether the column should hide its header text. A tooltip with the column's header text will be
     * displayed when the column header is focused instead. Note that this prop is specifically for
     * columns that contain ActionButtons in place of text content.
     */
    hideHeader?: boolean;
}
