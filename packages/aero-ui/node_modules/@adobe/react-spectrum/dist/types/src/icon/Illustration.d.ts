import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import { JSX, ReactElement } from 'react';
export interface IllustrationProps extends DOMProps, AriaLabelingProps, StyleProps {
    /**
     * A screen reader only label for the Illustration.
     */
    'aria-label'?: string;
    /**
     * The content to display. Should be an SVG.
     */
    children: ReactElement<any>;
    /**
     * A slot to place the illustration in.
     *
     * @default 'illustration'
     */
    slot?: string;
    /**
     * Indicates whether the element is exposed to an accessibility API.
     */
    'aria-hidden'?: boolean | 'false' | 'true';
}
export type IllustrationPropsWithoutChildren = Omit<IllustrationProps, 'children'>;
/**
 * Wrapper component for illustrations. Use this to wrap your svg element for a custom illustration.
 */
export declare function Illustration(props: IllustrationProps): JSX.Element;
