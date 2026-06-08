import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import $hc8A8$intlStringsmjs from "./intlStrings.mjs";
import {SliderBase as $b9b11fe36f370830$export$9418495bb635ebde} from "./SliderBase.mjs";
import {SliderThumb as $fb57abd91cce4cfe$export$2c1b491743890dec} from "./SliderThumb.mjs";
import "../slider_vars.css";
import $hc8A8$slider_vars_cssmjs from "../slider_vars_css.mjs";
import $hc8A8$react from "react";
import {useLocale as $hc8A8$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $hc8A8$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2020 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 







const $82cf6552fb616d80$export$826424dabc3dd705 = /*#__PURE__*/ (0, $hc8A8$react).forwardRef(function RangeSlider(props, ref) {
    let { onChange: onChange, onChangeEnd: onChangeEnd, value: value, defaultValue: defaultValue, getValueLabel: getValueLabel, ...otherProps } = props;
    let defaultThumbValues = undefined;
    if (defaultValue != null) defaultThumbValues = [
        defaultValue.start,
        defaultValue.end
    ];
    else if (value == null) // make sure that useSliderState knows we have two handles
    defaultThumbValues = [
        props.minValue ?? 0,
        props.maxValue ?? 100
    ];
    let baseProps = {
        ...otherProps,
        value: value != null ? [
            value.start,
            value.end
        ] : undefined,
        defaultValue: defaultThumbValues,
        onChange (v) {
            onChange?.({
                start: v[0],
                end: v[1]
            });
        },
        onChangeEnd (v) {
            onChangeEnd?.({
                start: v[0],
                end: v[1]
            });
        },
        getValueLabel: getValueLabel ? ([start, end])=>getValueLabel({
                start: start,
                end: end
            }) : undefined
    };
    let stringFormatter = (0, $hc8A8$useLocalizedStringFormatter)((0, ($parcel$interopDefault($hc8A8$intlStringsmjs))), '@react-spectrum/slider');
    let { direction: direction } = (0, $hc8A8$useLocale)();
    return /*#__PURE__*/ (0, $hc8A8$react).createElement((0, $b9b11fe36f370830$export$9418495bb635ebde), {
        ...baseProps,
        classes: 'spectrum-Slider--range',
        ref: ref
    }, ({ trackRef: trackRef, inputRef: inputRef, state: state })=>{
        let cssDirection = direction === 'rtl' ? 'right' : 'left';
        return /*#__PURE__*/ (0, $hc8A8$react).createElement((0, $hc8A8$react).Fragment, null, /*#__PURE__*/ (0, $hc8A8$react).createElement("div", {
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hc8A8$slider_vars_cssmjs))), 'spectrum-Slider-track'),
            style: {
                width: `${state.getThumbPercent(0) * 100}%`
            }
        }), /*#__PURE__*/ (0, $hc8A8$react).createElement((0, $fb57abd91cce4cfe$export$2c1b491743890dec), {
            index: 0,
            "aria-label": stringFormatter.format('minimum'),
            isDisabled: props.isDisabled,
            trackRef: trackRef,
            inputRef: inputRef,
            state: state,
            name: props.startName,
            form: props.form
        }), /*#__PURE__*/ (0, $hc8A8$react).createElement("div", {
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hc8A8$slider_vars_cssmjs))), 'spectrum-Slider-track'),
            style: {
                [cssDirection]: `${state.getThumbPercent(0) * 100}%`,
                width: `${Math.abs(state.getThumbPercent(0) - state.getThumbPercent(1)) * 100}%`
            }
        }), /*#__PURE__*/ (0, $hc8A8$react).createElement((0, $fb57abd91cce4cfe$export$2c1b491743890dec), {
            index: 1,
            "aria-label": stringFormatter.format('maximum'),
            isDisabled: props.isDisabled,
            trackRef: trackRef,
            state: state,
            name: props.endName,
            form: props.form
        }), /*#__PURE__*/ (0, $hc8A8$react).createElement("div", {
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hc8A8$slider_vars_cssmjs))), 'spectrum-Slider-track'),
            style: {
                width: `${(1 - state.getThumbPercent(1)) * 100}%`
            }
        }));
    });
});


export {$82cf6552fb616d80$export$826424dabc3dd705 as RangeSlider};
//# sourceMappingURL=RangeSlider.mjs.map
