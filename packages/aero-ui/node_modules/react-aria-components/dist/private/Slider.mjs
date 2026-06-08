import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {useSlider as $270k0$useSlider, useSliderThumb as $270k0$useSliderThumb} from "react-aria/useSlider";
import {clamp as $270k0$clamp} from "react-stately/private/utils/number";
import {filterDOMProps as $270k0$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $270k0$mergeProps} from "react-aria/mergeProps";
import $270k0$react, {createContext as $270k0$createContext, forwardRef as $270k0$forwardRef, useRef as $270k0$useRef, useContext as $270k0$useContext} from "react";
import {useSliderState as $270k0$useSliderState} from "react-stately/useSliderState";
import {useFocusRing as $270k0$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $270k0$useHover} from "react-aria/useHover";
import {useNumberFormatter as $270k0$useNumberFormatter} from "react-aria/useNumberFormatter";
import {VisuallyHidden as $270k0$VisuallyHidden} from "react-aria/VisuallyHidden";

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











const $806e559d0c2c64a0$export$e99164f0030f3bff = /*#__PURE__*/ (0, $270k0$createContext)(null);
const $806e559d0c2c64a0$export$1e7083018727fa60 = /*#__PURE__*/ (0, $270k0$createContext)(null);
const $806e559d0c2c64a0$export$f1fce0420cc6d8ee = /*#__PURE__*/ (0, $270k0$createContext)(null);
const $806e559d0c2c64a0$export$7ed6e0ce9ec48be7 = /*#__PURE__*/ (0, $270k0$createContext)(null);
const $806e559d0c2c64a0$export$6189c2744041d8f8 = /*#__PURE__*/ (0, $270k0$createContext)(null);
const $806e559d0c2c64a0$export$472062a354075cee = /*#__PURE__*/ (0, $270k0$forwardRef)(function Slider(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $806e559d0c2c64a0$export$e99164f0030f3bff);
    let trackRef = (0, $270k0$useRef)(null);
    let numberFormatter = (0, $270k0$useNumberFormatter)(props.formatOptions);
    let state = (0, $270k0$useSliderState)({
        ...props,
        numberFormatter: numberFormatter
    });
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, trackProps: trackProps, labelProps: labelProps, outputProps: outputProps } = (0, $270k0$useSlider)({
        ...props,
        label: label
    }, state, trackRef);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-Slider'
    });
    let DOMProps = (0, $270k0$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $806e559d0c2c64a0$export$1e7083018727fa60,
                state
            ],
            [
                $806e559d0c2c64a0$export$f1fce0420cc6d8ee,
                {
                    ...trackProps,
                    ref: trackRef
                }
            ],
            [
                $806e559d0c2c64a0$export$6189c2744041d8f8,
                outputProps
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $270k0$mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": state.orientation,
        "data-disabled": state.isDisabled || undefined
    }));
});
const $806e559d0c2c64a0$export$a590f758a961cb5b = /*#__PURE__*/ (0, $270k0$forwardRef)(function SliderOutput(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $806e559d0c2c64a0$export$6189c2744041d8f8);
    let { children: children, style: style, className: className, render: render, ...otherProps } = props;
    let state = (0, $270k0$useContext)($806e559d0c2c64a0$export$1e7083018727fa60);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).output, {
        ...otherProps,
        ...renderProps,
        ref: ref,
        "data-orientation": state.orientation || undefined,
        "data-disabled": state.isDisabled || undefined
    });
});
const $806e559d0c2c64a0$export$105594979f116971 = /*#__PURE__*/ (0, $270k0$forwardRef)(function SliderTrack(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $806e559d0c2c64a0$export$f1fce0420cc6d8ee);
    let state = (0, $270k0$useContext)($806e559d0c2c64a0$export$1e7083018727fa60);
    let { onHoverStart: onHoverStart, onHoverEnd: onHoverEnd, onHoverChange: onHoverChange, ...otherProps } = props;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $270k0$useHover)({
        onHoverStart: onHoverStart,
        onHoverEnd: onHoverEnd,
        onHoverChange: onHoverChange
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-SliderTrack',
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            isHovered: isHovered,
            state: state
        }
    });
    return /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $270k0$mergeProps)(otherProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-orientation": state.orientation || undefined,
        "data-disabled": state.isDisabled || undefined
    });
});
const $806e559d0c2c64a0$export$2c1b491743890dec = /*#__PURE__*/ (0, $270k0$forwardRef)(function SliderThumb(props, ref) {
    let { inputRef: userInputRef = null } = props;
    let state = (0, $270k0$useContext)($806e559d0c2c64a0$export$1e7083018727fa60);
    let { ref: trackRef } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($806e559d0c2c64a0$export$f1fce0420cc6d8ee);
    let { index: index = 0 } = props;
    let defaultInputRef = (0, $270k0$useRef)(null);
    let inputRef = userInputRef || defaultInputRef;
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { thumbProps: thumbProps, inputProps: inputProps, labelProps: labelProps, isDragging: isDragging, isFocused: isFocused, isDisabled: isDisabled } = (0, $270k0$useSliderThumb)({
        ...props,
        index: index,
        trackRef: trackRef,
        inputRef: inputRef,
        label: label
    }, state);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $270k0$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $270k0$useHover)(props);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $270k0$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $270k0$mergeProps)(DOMProps, thumbProps, hoverProps),
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
    }, /*#__PURE__*/ (0, $270k0$react).createElement((0, $270k0$VisuallyHidden), null, /*#__PURE__*/ (0, $270k0$react).createElement("input", {
        ref: inputRef,
        ...(0, $270k0$mergeProps)(inputProps, focusProps)
    })), /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef
                }
            ]
        ]
    }, renderProps.children));
});
const $806e559d0c2c64a0$export$2ede0a3f7b0c0db = /*#__PURE__*/ (0, $270k0$forwardRef)(function SliderFill(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $806e559d0c2c64a0$export$7ed6e0ce9ec48be7);
    let state = (0, $270k0$useContext)($806e559d0c2c64a0$export$1e7083018727fa60);
    let { onHoverStart: onHoverStart, onHoverEnd: onHoverEnd, onHoverChange: onHoverChange, ...otherProps } = props;
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $270k0$useHover)({
        onHoverStart: onHoverStart,
        onHoverEnd: onHoverEnd,
        onHoverChange: onHoverChange
    });
    let offset = props.offset != null ? (0, $270k0$clamp)(props.offset, state.getThumbMinValue(0), state.getThumbMaxValue(0)) : state.getThumbMinValue(0);
    let start = state.values.length > 1 ? state.getThumbPercent(0) * 100 : state.getValuePercent(offset) * 100;
    let end = state.values.length > 0 ? state.getThumbPercent(state.values.length - 1) * 100 : 0;
    let startPercent = Math.min(start, end);
    let endPercent = Math.max(start, end);
    let sizePercent = Math.max(0, endPercent - startPercent);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $270k0$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $270k0$mergeProps)(otherProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-orientation": state.orientation || undefined,
        "data-disabled": state.isDisabled || undefined
    });
});


export {$806e559d0c2c64a0$export$e99164f0030f3bff as SliderContext, $806e559d0c2c64a0$export$1e7083018727fa60 as SliderStateContext, $806e559d0c2c64a0$export$f1fce0420cc6d8ee as SliderTrackContext, $806e559d0c2c64a0$export$7ed6e0ce9ec48be7 as SliderFillContext, $806e559d0c2c64a0$export$6189c2744041d8f8 as SliderOutputContext, $806e559d0c2c64a0$export$472062a354075cee as Slider, $806e559d0c2c64a0$export$a590f758a961cb5b as SliderOutput, $806e559d0c2c64a0$export$105594979f116971 as SliderTrack, $806e559d0c2c64a0$export$2c1b491743890dec as SliderThumb, $806e559d0c2c64a0$export$2ede0a3f7b0c0db as SliderFill};
//# sourceMappingURL=Slider.mjs.map
