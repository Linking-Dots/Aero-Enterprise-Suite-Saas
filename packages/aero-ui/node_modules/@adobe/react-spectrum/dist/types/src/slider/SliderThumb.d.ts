import { AriaSliderThumbProps } from 'react-aria/useSlider';
import { ReactNode } from 'react';
import { RefObject } from '@react-types/shared';
import { SliderState } from 'react-stately/useSliderState';
interface SliderThumbProps extends AriaSliderThumbProps {
    trackRef: RefObject<HTMLElement | null>;
    inputRef?: RefObject<HTMLInputElement | null>;
    state: SliderState;
}
export declare function SliderThumb(props: SliderThumbProps): ReactNode;
export {};
