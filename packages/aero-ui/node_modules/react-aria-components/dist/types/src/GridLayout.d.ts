import { GridLayout as BaseGridLayout, GridLayoutOptions } from 'react-stately/useVirtualizerState';
import { LayoutOptionsDelegate } from './Virtualizer';
export declare class GridLayout<T, O extends GridLayoutOptions = GridLayoutOptions> extends BaseGridLayout<T, O> implements LayoutOptionsDelegate<GridLayoutOptions> {
    useLayoutOptions(): GridLayoutOptions;
}
