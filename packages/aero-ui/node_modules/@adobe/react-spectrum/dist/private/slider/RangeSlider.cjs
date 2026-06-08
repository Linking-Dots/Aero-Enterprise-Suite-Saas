var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $7caba0a08af5d1d6$exports = require("./intlStrings.cjs");
var $f72820e1213dc513$exports = require("./SliderBase.cjs");
var $492d3e2308ba15ca$exports = require("./SliderThumb.cjs");
require("../slider_vars.css");
var $2614471f25b42a54$exports = require("../slider_vars_css.cjs");
var $i0ykj$react = require("react");
var $i0ykj$reactariaI18nProvider = require("react-aria/I18nProvider");
var $i0ykj$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "RangeSlider", function () { return $6ddd66d2a3811661$export$826424dabc3dd705; });
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







const $6ddd66d2a3811661$export$826424dabc3dd705 = /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).forwardRef(function RangeSlider(props, ref) {
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
    let stringFormatter = (0, $i0ykj$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($7caba0a08af5d1d6$exports))), '@react-spectrum/slider');
    let { direction: direction } = (0, $i0ykj$reactariaI18nProvider.useLocale)();
    return /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement((0, $f72820e1213dc513$exports.SliderBase), {
        ...baseProps,
        classes: 'spectrum-Slider--range',
        ref: ref
    }, ({ trackRef: trackRef, inputRef: inputRef, state: state })=>{
        let cssDirection = direction === 'rtl' ? 'right' : 'left';
        return /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement((0, ($parcel$interopDefault($i0ykj$react))).Fragment, null, /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement("div", {
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($2614471f25b42a54$exports))), 'spectrum-Slider-track'),
            style: {
                width: `${state.getThumbPercent(0) * 100}%`
            }
        }), /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement((0, $492d3e2308ba15ca$exports.SliderThumb), {
            index: 0,
            "aria-label": stringFormatter.format('minimum'),
            isDisabled: props.isDisabled,
            trackRef: trackRef,
            inputRef: inputRef,
            state: state,
            name: props.startName,
            form: props.form
        }), /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement("div", {
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($2614471f25b42a54$exports))), 'spectrum-Slider-track'),
            style: {
                [cssDirection]: `${state.getThumbPercent(0) * 100}%`,
                width: `${Math.abs(state.getThumbPercent(0) - state.getThumbPercent(1)) * 100}%`
            }
        }), /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement((0, $492d3e2308ba15ca$exports.SliderThumb), {
            index: 1,
            "aria-label": stringFormatter.format('maximum'),
            isDisabled: props.isDisabled,
            trackRef: trackRef,
            state: state,
            name: props.endName,
            form: props.form
        }), /*#__PURE__*/ (0, ($parcel$interopDefault($i0ykj$react))).createElement("div", {
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($2614471f25b42a54$exports))), 'spectrum-Slider-track'),
            style: {
                width: `${(1 - state.getThumbPercent(1)) * 100}%`
            }
        }));
    });
});


//# sourceMappingURL=RangeSlider.cjs.map
