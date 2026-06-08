import { DimensionValue, DOMProps, GridStyleProps } from '@react-types/shared';
import React, { ReactNode } from 'react';
export interface GridProps extends DOMProps, GridStyleProps {
    /** Children of the grid container. */
    children: ReactNode;
}
/**
 * A layout container using CSS grid. Supports Spectrum dimensions as values to
 * ensure consistent and adaptive sizing and spacing.
 */
export declare const Grid: React.ForwardRefExoticComponent<GridProps & React.RefAttributes<import("@react-types/shared").DOMRefValue<HTMLDivElement>>>;
/**
 * Can be used to make a repeating fragment of the columns or rows list.
 * See [MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/repeat).
 *
 * @param count - The number of times to repeat the fragment.
 * @param repeat - The fragment to repeat.
 */
export declare function repeat(count: number | 'auto-fill' | 'auto-fit', repeat: DimensionValue | DimensionValue[]): string;
/**
 * Defines a size range greater than or equal to min and less than or equal to max.
 * See [MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/minmax).
 *
 * @param min - The minimum size.
 * @param max - The maximum size.
 */
export declare function minmax(min: DimensionValue, max: DimensionValue): string;
/**
 * Clamps a given size to an available size.
 * See [MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/fit-content).
 *
 * @param dimension - The size to clamp.
 */
export declare function fitContent(dimension: DimensionValue): string;
