var $048d76b84370f141$exports = require("./utils.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $1Kg7x$reactariauseSlider = require("react-aria/useSlider");
var $1Kg7x$reactstatelyprivateutilsnumber = require("react-stately/private/utils/number");
var $1Kg7x$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $1Kg7x$reactariamergeProps = require("react-aria/mergeProps");
var $1Kg7x$react = require("react");
var $1Kg7x$reactstatelyuseSliderState = require("react-stately/useSliderState");
var $1Kg7x$reactariauseFocusRing = require("react-aria/useFocusRing");
var $1Kg7x$reactariauseHover = require("react-aria/useHover");
var $1Kg7x$reactariauseNumberFormatter = require("react-aria/useNumberFormatter");
var $1Kg7x$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SliderContext", function () { return $30cd4abd8dfcbef7$export$e99164f0030f3bff; });
$parcel$export(module.exports, "SliderStateContext", function () { return $30cd4abd8dfcbef7$export$1e7083018727fa60; });
$parcel$export(module.exports, "SliderTrackContext", function () { return $30cd4abd8dfcbef7$export$f1fce0420cc6d8ee; });
$parcel$export(module.exports, "SliderFillContext", function () { return $30cd4abd8dfcbef7$export$7ed6e0ce9ec48be7; });
$parcel$export(module.exports, "SliderOutputContext", function () { return $30cd4abd8dfcbef7$export$6189c2744041d8f8; });
$parcel$export(module.exports, "Slider", function () { return $30cd4abd8dfcbef7$export$472062a354075cee; });
$parcel$export(module.exports, "SliderOutput", function () { return $30cd4abd8dfcbef7$export$a590f758a961cb5b; });
$parcel$export(module.exports, "SliderTrack", function () { return $30cd4abd8dfcbef7$export$105594979f116971; });
$parcel$export(module.exports, "SliderThumb", function () { return $30cd4abd8dfcbef7$export$2c1b491743890dec; });
$parcel$export(module.exports, "SliderFill", function () { return $30cd4abd8dfcbef7$export$2ede0a3f7b0c0db; });
/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 











const $30cd4abd8dfcbef7$export$e99164f0030f3bff = /*#__PURE__*/ (0, $1Kg7x$react.createContext)(null);
const $30cd4abd8dfcbef7$export$1e7083018727fa60 = /*#__PURE__*/ (0, $1Kg7x$react.createContext)(null);
const $30cd4abd8dfcbef7$export$f1fce0420cc6d8ee = /*#__PURE__*/ (0, $1Kg7x$react.createContext)(null);
const $30cd4abd8dfcbef7$export$7ed6e0ce9ec48be7 = /*#__PURE__*/ (0, $1Kg7x$react.createContext)(null);
const $30cd4abd8dfcbef7$export$6189c2744041d8f8 = /*#__PURE__*/ (0, $1Kg7x$react.createContext)(null);
const $30cd4abd8dfcbef7$export$472062a354075cee = /*#__PURE__*/ (0, $1Kg7x$react.forwardRef)(function Slider(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $30cd4abd8dfcbef7$export$e99164f0030f3bff);
    let trackRef = (0, $1Kg7x$react.useRef)(null);
    let numberFormatter = (0, $1Kg7x$reactariauseNumberFormatter.useNumberFormatter)(props.formatOptions);
    let state = (0, $1Kg7x$reactstatelyuseSliderState.useSliderState)({
        ...props,
        numberFormatter: numberFormatter
    });
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, trackProps: trackProps, labelProps: labelProps, outputProps: outputProps } = (0, $1Kg7x$reactariauseSlider.useSlider)({
        ...props,
        label: label
    }, state, trackRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-Slider'
    });
    let DOMProps = (0, $1Kg7x$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $30cd4abd8dfcbef7$export$1e7083018727fa60,
                state
            ],
            [
                $30cd4abd8dfcbef7$export$f1fce0420cc6d8ee,
                {
                    ...trackProps,
                    ref: trackRef
                }
            ],
            [
                $30cd4abd8dfcbef7$export$6189c2744041d8f8,
                outputProps
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $1Kg7x$reactariamergeProps.mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": state.orientation,
        "data-disabled": state.isDisabled || undefined
    }));
});
const $30cd4abd8dfcbef7$export$a590f758a961cb5b = /*#__PURE__*/ (0, $1Kg7x$react.forwardRef)(function SliderOutput(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $30cd4abd8dfcbef7$export$6189c2744041d8f8);
    let { children: children, style: style, className: className, render: render, ...otherProps } = props;
    let state = (0, $1Kg7x$react.useContext)($30cd4abd8dfcbef7$export$1e7083018727fa60);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        className: className,
        style: style,
        children: children,
        render: render,
        defaultChildren: state.getFormattedValue(),
        defaultClassName: 'react-aria-SliderOutput',
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            state: state
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.dom).output, {
        ...otherProps,
        ...renderProps,
        ref: ref,
        "data-orientation": state.orientation || undefined,
        "data-disabled": state.isDisabled || undefined
    });
});
const $30cd4abd8dfcbef7$export$105594979f116971 = /*#__PURE__*/ (0, $1Kg7x$react.forwardRef)(function SliderTrack(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $30cd4abd8dfcbef7$export$f1fce0420cc6d8ee);
    let state = (0, $1Kg7x$react.useContext)($30cd4abd8dfcbef7$export$1e7083018727fa60);
    let { onHoverStart: onHoverStart, onHoverEnd: onHoverEnd, onHoverChange: onHoverChange, ...otherProps } = props;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1Kg7x$reactariauseHover.useHover)({
        onHoverStart: onHoverStart,
        onHoverEnd: onHoverEnd,
        onHoverChange: onHoverChange
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-SliderTrack',
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            isHovered: isHovered,
            state: state
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $1Kg7x$reactariamergeProps.mergeProps)(otherProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-orientation": state.orientation || undefined,
        "data-disabled": state.isDisabled || undefined
    });
});
const $30cd4abd8dfcbef7$export$2c1b491743890dec = /*#__PURE__*/ (0, $1Kg7x$react.forwardRef)(function SliderThumb(props, ref) {
    let { inputRef: userInputRef = null } = props;
    let state = (0, $1Kg7x$react.useContext)($30cd4abd8dfcbef7$export$1e7083018727fa60);
    let { ref: trackRef } = (0, $048d76b84370f141$exports.useSlottedContext)($30cd4abd8dfcbef7$export$f1fce0420cc6d8ee);
    let { index: index = 0 } = props;
    let defaultInputRef = (0, $1Kg7x$react.useRef)(null);
    let inputRef = userInputRef || defaultInputRef;
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { thumbProps: thumbProps, inputProps: inputProps, labelProps: labelProps, isDragging: isDragging, isFocused: isFocused, isDisabled: isDisabled } = (0, $1Kg7x$reactariauseSlider.useSliderThumb)({
        ...props,
        index: index,
        trackRef: trackRef,
        inputRef: inputRef,
        label: label
    }, state);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $1Kg7x$reactariauseFocusRing.useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1Kg7x$reactariauseHover.useHover)(props);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-SliderThumb',
        values: {
            state: state,
            isHovered: isHovered,
            isDragging: isDragging,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled
        }
    });
    let DOMProps = (0, $1Kg7x$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $1Kg7x$reactariamergeProps.mergeProps)(DOMProps, thumbProps, hoverProps),
        ...renderProps,
        ref: ref,
        style: {
            ...thumbProps.style,
            ...renderProps.style
        },
        "data-hovered": isHovered || undefined,
        "data-dragging": isDragging || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $1Kg7x$reactariaVisuallyHidden.VisuallyHidden), null, /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement("input", {
        ref: inputRef,
        ...(0, $1Kg7x$reactariamergeProps.mergeProps)(inputProps, focusProps)
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ]
        ]
    }, renderProps.children));
});
const $30cd4abd8dfcbef7$export$2ede0a3f7b0c0db = /*#__PURE__*/ (0, $1Kg7x$react.forwardRef)(function SliderFill(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $30cd4abd8dfcbef7$export$7ed6e0ce9ec48be7);
    let state = (0, $1Kg7x$react.useContext)($30cd4abd8dfcbef7$export$1e7083018727fa60);
    let { onHoverStart: onHoverStart, onHoverEnd: onHoverEnd, onHoverChange: onHoverChange, ...otherProps } = props;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1Kg7x$reactariauseHover.useHover)({
        onHoverStart: onHoverStart,
        onHoverEnd: onHoverEnd,
        onHoverChange: onHoverChange
    });
    let offset = props.offset != null ? (0, $1Kg7x$reactstatelyprivateutilsnumber.clamp)(props.offset, state.getThumbMinValue(0), state.getThumbMaxValue(0)) : state.getThumbMinValue(0);
    let start = state.values.length > 1 ? state.getThumbPercent(0) * 100 : state.getValuePercent(offset) * 100;
    let end = state.values.length > 0 ? state.getThumbPercent(state.values.length - 1) * 100 : 0;
    let startPercent = Math.min(start, end);
    let endPercent = Math.max(start, end);
    let sizePercent = Math.max(0, endPercent - startPercent);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-SliderFill',
        defaultStyle: state.orientation === 'vertical' ? {
            position: 'absolute',
            bottom: `${startPercent}%`,
            height: `${sizePercent}%`,
            width: '100%'
        } : {
            position: 'absolute',
            insetInlineStart: `${startPercent}%`,
            width: `${sizePercent}%`,
            height: '100%'
        },
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            isHovered: isHovered,
            state: state
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1Kg7x$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $1Kg7x$reactariamergeProps.mergeProps)(otherProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-orientation": state.orientation || undefined,
        "data-disabled": state.isDisabled || undefined
    });
});


//# sourceMappingURL=Slider.cjs.map
