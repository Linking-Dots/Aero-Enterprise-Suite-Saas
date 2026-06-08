import { AriaStepListProps } from 'react-aria/private/steplist/useStepList';
import { DOMRef, Orientation, StyleProps } from '@react-types/shared';
import React, { ReactElement } from 'react';
export interface SpectrumStepListProps<T> extends AriaStepListProps<T>, StyleProps {
    /**
     * Whether the step list should be displayed with a emphasized style.
     *
     * @default false
     */
    isEmphasized?: boolean;
    /**
     * The orientation of the step list.
     *
     * @default 'horizontal'
     */
    orientation?: Orientation;
    /**
     * The size of the step list.
     *
     * @default 'M'
     */
    size?: 'S' | 'M' | 'L' | 'XL';
}
export declare const StepList: <T>(props: SpectrumStepListProps<T> & {
    ref?: DOMRef<HTMLOListElement> | undefined;
}) => ReactElement<unknown, string | React.JSXElementConstructor<any>>;
