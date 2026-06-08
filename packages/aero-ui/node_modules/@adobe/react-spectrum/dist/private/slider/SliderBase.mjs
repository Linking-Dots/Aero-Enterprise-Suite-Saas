import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../slider_vars.css";
import $e12bK$slider_vars_cssmjs from "../slider_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useSlider as $e12bK$useSlider} from "react-aria/useSlider";
import $e12bK$react, {useRef as $e12bK$useRef} from "react";
import {useSliderState as $e12bK$useSliderState} from "react-stately/useSliderState";
import {useNumberFormatter as $e12bK$useNumberFormatter} from "react-aria/useNumberFormatter";


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









const $b9b11fe36f370830$export$9418495bb635ebde = /*#__PURE__*/ (0, $e12bK$react).forwardRef(function SliderBase(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { isDisabled: isDisabled, children: children, classes: classes, style: style, labelPosition: labelPosition = 'top', getValueLabel: getValueLabel, showValueLabel: showValueLabel = !!props.label, formatOptions: formatOptions, minValue: minValue = 0, maxValue: maxValue = 100, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    // `Math.abs(Math.sign(a) - Math.sign(b)) === 2` is true if the values have a different sign.
    let alwaysDisplaySign = Math.abs(Math.sign(minValue) - Math.sign(maxValue)) === 2;
    if (alwaysDisplaySign) {
        if (formatOptions != null) {
            if (!('signDisplay' in formatOptions)) formatOptions = {
                ...formatOptions,
                signDisplay: 'exceptZero'
            };
        } else formatOptions = {
            signDisplay: 'exceptZero'
        };
    }
    const formatter = (0, $e12bK$useNumberFormatter)(formatOptions);
    const state = (0, $e12bK$useSliderState)({
        ...props,
        numberFormatter: formatter,
        minValue: minValue,
        maxValue: maxValue
    });
    let trackRef = (0, $e12bK$useRef)(null);
    let { groupProps: groupProps, trackProps: trackProps, labelProps: labelProps, outputProps: outputProps } = (0, $e12bK$useSlider)(props, state, trackRef);
    let inputRef = (0, $e12bK$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputRef);
    let displayValue = '';
    let maxLabelLength = null;
    if (typeof getValueLabel === 'function') {
        displayValue = getValueLabel(state.values);
        switch(state.values.length){
            case 1:
                maxLabelLength = Math.max(getValueLabel([
                    minValue
                ]).length, getValueLabel([
                    maxValue
                ]).length);
                break;
            case 2:
                // Try all possible combinations of min and max values.
                maxLabelLength = Math.max(getValueLabel([
                    minValue,
                    minValue
                ]).length, getValueLabel([
                    minValue,
                    maxValue
                ]).length, getValueLabel([
                    maxValue,
                    minValue
                ]).length, getValueLabel([
                    maxValue,
                    maxValue
                ]).length);
                break;
            default:
                throw new Error('Only sliders with 1 or 2 handles are supported!');
        }
    } else {
        maxLabelLength = Math.max([
            ...formatter.format(minValue)
        ].length, [
            ...formatter.format(maxValue)
        ].length);
        switch(state.values.length){
            case 1:
                displayValue = state.getThumbValueLabel(0);
                break;
            case 2:
                // This should really use the NumberFormat#formatRange proposal...
                // https://github.com/tc39/ecma402/issues/393
                // https://github.com/tc39/proposal-intl-numberformat-v3#formatrange-ecma-402-393
                displayValue = `${state.getThumbValueLabel(0)} \u{2013} ${state.getThumbValueLabel(1)}`;
                maxLabelLength = 3 + 2 * Math.max(maxLabelLength, [
                    ...formatter.format(minValue)
                ].length, [
                    ...formatter.format(maxValue)
                ].length);
                break;
            default:
                throw new Error('Only sliders with 1 or 2 handles are supported!');
        }
    }
    let labelNode = /*#__PURE__*/ (0, $e12bK$react).createElement("label", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider-label'),
        ...labelProps
    }, props.label);
    let valueNode = /*#__PURE__*/ (0, $e12bK$react).createElement("output", {
        ...outputProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider-value'),
        style: maxLabelLength != null ? {
            width: `${maxLabelLength}ch`,
            minWidth: `${maxLabelLength}ch`
        } : undefined
    }, displayValue);
    return /*#__PURE__*/ (0, $e12bK$react).createElement("div", {
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider', {
            'spectrum-Slider--positionTop': labelPosition === 'top',
            'spectrum-Slider--positionSide': labelPosition === 'side',
            'is-disabled': isDisabled
        }, classes, styleProps.className),
        style: {
            ...style,
            ...styleProps.style
        },
        ...groupProps
    }, props.label && /*#__PURE__*/ (0, $e12bK$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider-labelContainer'),
        role: "presentation"
    }, props.label && labelNode, props.contextualHelp && /*#__PURE__*/ (0, $e12bK$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            actionButton: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider-contextualHelp')
            }
        }
    }, props.contextualHelp), labelPosition === 'top' && showValueLabel && valueNode), /*#__PURE__*/ (0, $e12bK$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider-controls'),
        ref: trackRef,
        ...trackProps,
        role: "presentation"
    }, children({
        trackRef: trackRef,
        inputRef: inputRef,
        state: state
    })), labelPosition === 'side' && /*#__PURE__*/ (0, $e12bK$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($e12bK$slider_vars_cssmjs))), 'spectrum-Slider-valueLabelContainer'),
        role: "presentation"
    }, showValueLabel && valueNode));
});


export {$b9b11fe36f370830$export$9418495bb635ebde as SliderBase};
//# sourceMappingURL=SliderBase.mjs.map
