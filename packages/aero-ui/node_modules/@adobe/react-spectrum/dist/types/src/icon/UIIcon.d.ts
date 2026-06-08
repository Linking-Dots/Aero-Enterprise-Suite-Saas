import { AriaLabelingProps, DOMProps, StyleProps } from '@react-types/shared';
import { JSX, ReactElement } from 'react';
export interface UIIconProps extends DOMProps, AriaLabelingProps, StyleProps {
    children: ReactElement<any>;
    slot?: string;
    /**
     * Indicates whether the element is exposed to an accessibility API.
     */
    'aria-hidden'?: boolean | 'false' | 'true';
}
export type UIIconPropsWithoutChildren = Omit<UIIconProps, 'children'>;
export declare function UIIcon(props: UIIconProps): JSX.Element;
