import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import $cZbtO$intlStringsjs from "./intlStrings.js";
import {SliderBase as $423c6feafac04ea6$export$9418495bb635ebde} from "./SliderBase.js";
import {SliderThumb as $adc3a7de1c061d22$export$2c1b491743890dec} from "./SliderThumb.js";
import "../slider_vars.css";
import $cZbtO$slider_vars_cssmjs from "../slider_vars_css.mjs";
import $cZbtO$react from "react";
import {useLocale as $cZbtO$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $cZbtO$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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







const $fe33cb66aef2fd43$export$826424dabc3dd705 = /*#__PURE__*/ (0, $cZbtO$react).forwardRef(function RangeSlider(props, ref) {
    let { onChange: onChange, onChangeEnd: onChangeEnd, value: value, defaultValue: defaultValue, getValueLabel: getValueLabel, ...otherProps } = props;
    let defaultThumbValues = undefined;
    var _props_minValue, _props_maxValue;
    if (defaultValue != null) defaultThumbValues = [
        defaultValue.start,
        defaultValue.end
    ];
    else if (value == null) // make sure that useSliderState knows we have two handles
    defaultThumbValues = [
        (_props_minValue = props.minValue) !== null && _props_minValue !== void 0 ? _props_minValue : 0,
        (_props_maxValue = props.maxValue) !== null && _props_maxValue !== void 0 ? _props_maxValue : 100
    ];
    let baseProps = {
        ...otherProps,
        value: value != null ? [
            value.start,
            value.end
        ] : undefined,
        defaultValue: defaultThumbValues,
        onChange (v) {
            onChange === null || onChange === void 0 ? void 0 : onChange({
                start: v[0],
                end: v[1]
            });
        },
        onChangeEnd (v) {
            onChangeEnd === null || onChangeEnd === void 0 ? void 0 : onChangeEnd({
                start: v[0],
                end: v[1]
            });
        },
        getValueLabel: getValueLabel ? ([start, end])=>getValueLabel({
                start: start,
                end: end
            }) : undefined
    };
    let stringFormatter = (0, $cZbtO$useLocalizedStringFormatter)((0, ($parcel$interopDefault($cZbtO$intlStringsjs))), '@react-spectrum/slider');
    let { direction: direction } = (0, $cZbtO$useLocale)();
    return /*#__PURE__*/ (0, $cZbtO$react).createElement((0, $423c6feafac04ea6$export$9418495bb635ebde), {
        ...baseProps,
        classes: 'spectrum-Slider--range',
        ref: ref
    }, ({ trackRef: trackRef, inputRef: inputRef, state: state })=>{
        let cssDirection = direction === 'rtl' ? 'right' : 'left';
        return /*#__PURE__*/ (0, $cZbtO$react).createElement((0, $cZbtO$react).Fragment, null, /*#__PURE__*/ (0, $cZbtO$react).createElement("div", {
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZbtO$slider_vars_cssmjs))), 'spectrum-Slider-track'),
            style: {
                width: `${state.getThumbPercent(0) * 100}%`
            }
        }), /*#__PURE__*/ (0, $cZbtO$react).createElement((0, $adc3a7de1c061d22$export$2c1b491743890dec), {
            index: 0,
            "aria-label": stringFormatter.format('minimum'),
            isDisabled: props.isDisabled,
            trackRef: trackRef,
            inputRef: inputRef,
            state: state,
            name: props.startName,
            form: props.form
        }), /*#__PURE__*/ (0, $cZbtO$react).createElement("div", {
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZbtO$slider_vars_cssmjs))), 'spectrum-Slider-track'),
            style: {
                [cssDirection]: `${state.getThumbPercent(0) * 100}%`,
                width: `${Math.abs(state.getThumbPercent(0) - state.getThumbPercent(1)) * 100}%`
            }
        }), /*#__PURE__*/ (0, $cZbtO$react).createElement((0, $adc3a7de1c061d22$export$2c1b491743890dec), {
            index: 1,
            "aria-label": stringFormatter.format('maximum'),
            isDisabled: props.isDisabled,
            trackRef: trackRef,
            state: state,
            name: props.endName,
            form: props.form
        }), /*#__PURE__*/ (0, $cZbtO$react).createElement("div", {
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cZbtO$slider_vars_cssmjs))), 'spectrum-Slider-track'),
            style: {
                width: `${(1 - state.getThumbPercent(1)) * 100}%`
            }
        }));
    });
});


export {$fe33cb66aef2fd43$export$826424dabc3dd705 as RangeSlider};
//# sourceMappingURL=RangeSlider.js.map
