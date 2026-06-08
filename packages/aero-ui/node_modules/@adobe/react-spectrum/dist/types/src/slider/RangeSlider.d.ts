import { RangeValue } from '@react-types/shared';
import React from 'react';
import { SpectrumBarSliderBase } from './SliderBase';
export interface SpectrumRangeSliderProps extends SpectrumBarSliderBase<RangeValue<number>> {
    /**
     * The name of the start input element, used when submitting an HTML form. See
     * [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#htmlattrdefname).
     */
    startName?: string;
    /**
     * The name of the end input element, used when submitting an HTML form. See
     * [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#htmlattrdefname).
     */
    endName?: string;
    /**
     * The `<form>` element to associate the slider with.
     * The value of this attribute must be the id of a `<form>` in the same document.
     * See [MDN](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/input#form).
     */
    form?: string;
}
/**
 * RangeSliders allow users to quickly select a subset range. They should be used when the upper and
 * lower bounds to the range are invariable.
 */
export declare const RangeSlider: React.ForwardRefExoticComponent<SpectrumRangeSliderProps & React.RefAttributes<import("@react-types/shared").FocusableRefValue<HTMLDivElement, HTMLDivElement>>>;
