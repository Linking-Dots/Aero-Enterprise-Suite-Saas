import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ColorThumb as $ebb08d0afd4c10ba$export$a3cc47cee1c1ccc} from "./ColorThumb.mjs";
import {Label as $f6f5235bab1fa21e$export$b04be29aa201d4f5} from "../label/Label.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../colorslider_vars.css";
import $leRal$colorslider_vars_cssmjs from "../colorslider_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useColorSlider as $leRal$useColorSlider} from "react-aria/useColorSlider";
import {ColorSliderContext as $leRal$ColorSliderContext} from "react-aria-components/ColorSlider";
import $leRal$react, {useRef as $leRal$useRef, useState as $leRal$useState} from "react";
import {useColorSliderState as $leRal$useColorSliderState} from "react-stately/useColorSliderState";
import {useContextProps as $leRal$useContextProps} from "react-aria-components/slots";
import {useFocus as $leRal$useFocus} from "react-aria/useFocus";
import {useFocusVisible as $leRal$useFocusVisible} from "react-aria/useFocusVisible";
import {useLocale as $leRal$useLocale} from "react-aria/I18nProvider";


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















const $60ebf63a8fdaa76a$export$44fd664bcca5b6fb = /*#__PURE__*/ (0, $leRal$react).forwardRef(function ColorSlider(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let inputRef = (0, $leRal$useRef)(null);
    let trackRef = (0, $leRal$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputRef);
    [props, domRef] = (0, $leRal$useContextProps)(props, domRef, (0, $leRal$ColorSliderContext));
    let { isDisabled: isDisabled, channel: channel, orientation: orientation, label: label, showValueLabel: showValueLabel, 'aria-label': ariaLabel } = props;
    let vertical = orientation === 'vertical';
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let { locale: locale } = (0, $leRal$useLocale)();
    let state = (0, $leRal$useColorSliderState)({
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
    let { inputProps: inputProps, thumbProps: thumbProps, trackProps: trackProps, labelProps: labelProps, outputProps: outputProps } = (0, $leRal$useColorSlider)({
        ...props,
        label: label,
        'aria-label': ariaLabel,
        trackRef: trackRef,
        inputRef: inputRef
    }, state);
    let { isFocusVisible: isFocusVisible } = (0, $leRal$useFocusVisible)();
    let [isFocused, setIsFocused] = (0, $leRal$useState)(false);
    let { focusProps: focusProps } = (0, $leRal$useFocus)({
        isDisabled: isDisabled,
        onFocusChange: setIsFocused
    });
    return /*#__PURE__*/ (0, $leRal$react).createElement("div", {
        ref: domRef,
        ...styleProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), {
            'spectrum-ColorSlider-container--horizontal': !vertical,
            'spectrum-ColorSlider-container--vertical': vertical
        }, styleProps.className)
    }, label && /*#__PURE__*/ (0, $leRal$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-labelContainer')
    }, /*#__PURE__*/ (0, $leRal$react).createElement((0, $f6f5235bab1fa21e$export$b04be29aa201d4f5), labelProps, label), props.contextualHelp && /*#__PURE__*/ (0, $leRal$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            actionButton: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-contextualHelp')
            }
        }
    }, props.contextualHelp), showValueLabel && /*#__PURE__*/ (0, $leRal$react).createElement((0, $f6f5235bab1fa21e$export$b04be29aa201d4f5), {
        elementType: "span",
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-valueLabel')
    }, /*#__PURE__*/ (0, $leRal$react).createElement("output", outputProps, state.value.formatChannelValue(channel, locale)))), /*#__PURE__*/ (0, $leRal$react).createElement("div", {
        ...trackProps,
        ref: trackRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), 'spectrum-ColorSlider', {
            'is-disabled': isDisabled,
            'spectrum-ColorSlider--vertical': vertical
        })
    }, /*#__PURE__*/ (0, $leRal$react).createElement((0, $ebb08d0afd4c10ba$export$a3cc47cee1c1ccc), {
        value: state.getDisplayColor(),
        isFocused: isFocused && isFocusVisible,
        isDisabled: isDisabled,
        isDragging: state.isThumbDragging(0),
        containerRef: trackRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-handle'),
        ...thumbProps
    }, /*#__PURE__*/ (0, $leRal$react).createElement("input", {
        ...inputProps,
        ...focusProps,
        ref: inputRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($leRal$colorslider_vars_cssmjs))), 'spectrum-ColorSlider-slider')
    }))));
});


export {$60ebf63a8fdaa76a$export$44fd664bcca5b6fb as ColorSlider};
//# sourceMappingURL=ColorSlider.mjs.map
