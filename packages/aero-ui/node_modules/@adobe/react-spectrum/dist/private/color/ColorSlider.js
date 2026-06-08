import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ColorThumb as $7f568464139e11ee$export$a3cc47cee1c1ccc} from "./ColorThumb.js";
import {Label as $323da7a023c7a11f$export$b04be29aa201d4f5} from "../label/Label.js";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import "../colorslider_vars.css";
import $1nvEp$colorslider_vars_cssmjs from "../colorslider_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useColorSlider as $1nvEp$useColorSlider} from "react-aria/useColorSlider";
import {ColorSliderContext as $1nvEp$ColorSliderContext} from "react-aria-components/ColorSlider";
import $1nvEp$react, {useRef as $1nvEp$useRef, useState as $1nvEp$useState} from "react";
import {useColorSliderState as $1nvEp$useColorSliderState} from "react-stately/useColorSliderState";
import {useContextProps as $1nvEp$useContextProps} from "react-aria-components/slots";
import {useFocus as $1nvEp$useFocus} from "react-aria/useFocus";
import {useFocusVisible as $1nvEp$useFocusVisible} from "react-aria/useFocusVisible";
import {useLocale as $1nvEp$useLocale} from "react-aria/I18nProvider";


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















const $5234de165996dd10$export$44fd664bcca5b6fb = /*#__PURE__*/ (0, $1nvEp$react).forwardRef(function ColorSlider(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let inputRef = (0, $1nvEp$useRef)(null);
    let trackRef = (0, $1nvEp$useRef)(null);
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref, inputRef);
    [props, domRef] = (0, $1nvEp$useContextProps)(props, domRef, (0, $1nvEp$ColorSliderContext));
    let { isDisabled: isDisabled, channel: channel, orientation: orientation, label: label, showValueLabel: showValueLabel, 'aria-label': ariaLabel } = props;
    let vertical = orientation === 'vertical';
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let { locale: locale } = (0, $1nvEp$useLocale)();
    let state = (0, $1nvEp$useColorSliderState)({
        ...props,
        locale: locale
    });
    // If vertical and a label is provided, use it as an aria-label instead.
    if (vertical && label) {
        ariaLabel = ariaLabel || (typeof label === 'string' ? label : undefined);
        label = null;
    }
    // If no external label, aria-label or aria-labelledby is provided,
    // default to displaying the localized channel value.
    // Specifically check if label is undefined. If label is `null` then display no visible label.
    // A default aria-label is provided by useColorSlider in that case.
    if (label === undefined && !ariaLabel && !props['aria-labelledby'] && !vertical) label = state.value.getChannelName(channel, locale);
    // Show the value label by default if there is a visible label
    if (showValueLabel == null) showValueLabel = !!label;
    let { inputProps: inputProps, thumbProps: thumbProps, trackProps: trackProps, labelProps: labelProps, outputProps: outputProps } = (0, $1nvEp$useColorSlider)({
        ...props,
        label: label,
        'aria-label': ariaLabel,
        trackRef: trackRef,
        inputRef: inputRef
    }, state);
    let { isFocusVisible: isFocusVisible } = (0, $1nvEp$useFocusVisible)();
    let [isFocused, setIsFocused] = (0, $1nvEp$useState)(false);
    let { focusProps: focusProps } = (0, $1nvEp$useFocus)({
        isDisabled: isDisabled,
        onFocusChange: setIsFocused
    });
    return /*#__PURE__*/ (0, $1nvEp$react).createElement("div", {
        ref: domRef,
        ...styleProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), {
            'spectrum-ColorSlider-container--horizontal': !vertical,
            'spectrum-ColorSlider-container--vertical': vertical
        }, styleProps.className)
    }, label && /*#__PURE__*/ (0, $1nvEp$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-labelContainer')
    }, /*#__PURE__*/ (0, $1nvEp$react).createElement((0, $323da7a023c7a11f$export$b04be29aa201d4f5), labelProps, label), props.contextualHelp && /*#__PURE__*/ (0, $1nvEp$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            actionButton: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-contextualHelp')
            }
        }
    }, props.contextualHelp), showValueLabel && /*#__PURE__*/ (0, $1nvEp$react).createElement((0, $323da7a023c7a11f$export$b04be29aa201d4f5), {
        elementType: "span",
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-valueLabel')
    }, /*#__PURE__*/ (0, $1nvEp$react).createElement("output", outputProps, state.value.formatChannelValue(channel, locale)))), /*#__PURE__*/ (0, $1nvEp$react).createElement("div", {
        ...trackProps,
        ref: trackRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), 'spectrum-ColorSlider', {
            'is-disabled': isDisabled,
            'spectrum-ColorSlider--vertical': vertical
        })
    }, /*#__PURE__*/ (0, $1nvEp$react).createElement((0, $7f568464139e11ee$export$a3cc47cee1c1ccc), {
        value: state.getDisplayColor(),
        isFocused: isFocused && isFocusVisible,
        isDisabled: isDisabled,
        isDragging: state.isThumbDragging(0),
        containerRef: trackRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-handle'),
        ...thumbProps
    }, /*#__PURE__*/ (0, $1nvEp$react).createElement("input", {
        ...inputProps,
        ...focusProps,
        ref: inputRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1nvEp$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-slider')
    }))));
});


export {$5234de165996dd10$export$44fd664bcca5b6fb as ColorSlider};
//# sourceMappingURL=ColorSlider.js.map
