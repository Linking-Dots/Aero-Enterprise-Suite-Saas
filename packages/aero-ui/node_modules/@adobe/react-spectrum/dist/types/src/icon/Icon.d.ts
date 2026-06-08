import { AriaLabelingProps, DOMProps, IconColorValue, StyleProps } from '@react-types/shared';
import { JSX, ReactElement } from 'react';
export interface IconProps extends DOMProps, AriaLabelingProps, StyleProps {
    /**
     * A screen reader only label for the Icon.
     */
    'aria-label'?: string;
    /**
     * The content to display. Should be an SVG.
     */
    children: ReactElement<any>;
    /**
     * Size of Icon (changes based on scale).
     */
    size?: 'XXS' | 'XS' | 'S' | 'M' | 'L' | 'XL' | 'XXL';
    /**
     * A slot to place the icon in.
     *
     * @default 'icon'
     */
    slot?: string;
    /**
     * Indicates whether the element is exposed to an accessibility API.
     */
    'aria-hidden'?: boolean | 'false' | 'true';
    /**
     * Color of the Icon.
     */
    color?: IconColorValue;
}
export type IconPropsWithoutChildren = Omit<IconProps, 'children'>;
/**
 * Spectrum icons are clear, minimal, and consistent across platforms. They follow the focused and
 * rational principles of the design system in both metaphor and style.
 */
export declare function Icon(props: IconProps): JSX.Element;
