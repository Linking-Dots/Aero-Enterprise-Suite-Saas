var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $9b9b2ae635bd46b3$exports = require("./ColorThumb.cjs");
var $b881bddc71fd043e$exports = require("../label/Label.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../colorslider_vars.css");
var $626f4fbff06027ee$exports = require("../colorslider_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $krgmV$reactariauseColorSlider = require("react-aria/useColorSlider");
var $krgmV$reactariacomponentsColorSlider = require("react-aria-components/ColorSlider");
var $krgmV$react = require("react");
var $krgmV$reactstatelyuseColorSliderState = require("react-stately/useColorSliderState");
var $krgmV$reactariacomponentsslots = require("react-aria-components/slots");
var $krgmV$reactariauseFocus = require("react-aria/useFocus");
var $krgmV$reactariauseFocusVisible = require("react-aria/useFocusVisible");
var $krgmV$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorSlider", function () { return $3b70a976df9d5d9d$export$44fd664bcca5b6fb; });
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















const $3b70a976df9d5d9d$export$44fd664bcca5b6fb = /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).forwardRef(function ColorSlider(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let inputRef = (0, $krgmV$react.useRef)(null);
    let trackRef = (0, $krgmV$react.useRef)(null);
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref, inputRef);
    [props, domRef] = (0, $krgmV$reactariacomponentsslots.useContextProps)(props, domRef, (0, $krgmV$reactariacomponentsColorSlider.ColorSliderContext));
    let { isDisabled: isDisabled, channel: channel, orientation: orientation, label: label, showValueLabel: showValueLabel, 'aria-label': ariaLabel } = props;
    let vertical = orientation === 'vertical';
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let { locale: locale } = (0, $krgmV$reactariaI18nProvider.useLocale)();
    let state = (0, $krgmV$reactstatelyuseColorSliderState.useColorSliderState)({
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
    let { inputProps: inputProps, thumbProps: thumbProps, trackProps: trackProps, labelProps: labelProps, outputProps: outputProps } = (0, $krgmV$reactariauseColorSlider.useColorSlider)({
        ...props,
        label: label,
        'aria-label': ariaLabel,
        trackRef: trackRef,
        inputRef: inputRef
    }, state);
    let { isFocusVisible: isFocusVisible } = (0, $krgmV$reactariauseFocusVisible.useFocusVisible)();
    let [isFocused, setIsFocused] = (0, $krgmV$react.useState)(false);
    let { focusProps: focusProps } = (0, $krgmV$reactariauseFocus.useFocus)({
        isDisabled: isDisabled,
        onFocusChange: setIsFocused
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement("div", {
        ref: domRef,
        ...styleProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), {
            'spectrum-ColorSlider-container--horizontal': !vertical,
            'spectrum-ColorSlider-container--vertical': vertical
        }, styleProps.className)
    }, label && /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), 'spectrum-ColorSlider-labelContainer')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement((0, $b881bddc71fd043e$exports.Label), labelProps, label), props.contextualHelp && /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            actionButton: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), 'spectrum-ColorSlider-contextualHelp')
            }
        }
    }, props.contextualHelp), showValueLabel && /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement((0, $b881bddc71fd043e$exports.Label), {
        elementType: "span",
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), 'spectrum-ColorSlider-valueLabel')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement("output", outputProps, state.value.formatChannelValue(channel, locale)))), /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement("div", {
        ...trackProps,
        ref: trackRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), 'spectrum-ColorSlider', {
            'is-disabled': isDisabled,
            'spectrum-ColorSlider--vertical': vertical
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement((0, $9b9b2ae635bd46b3$exports.ColorThumb), {
        value: state.getDisplayColor(),
        isFocused: isFocused && isFocusVisible,
        isDisabled: isDisabled,
        isDragging: state.isThumbDragging(0),
        containerRef: trackRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), 'spectrum-ColorSlider-handle'),
        ...thumbProps
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($krgmV$react))).createElement("input", {
        ...inputProps,
        ...focusProps,
        ref: inputRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($626f4fbff06027ee$exports))), 'spectrum-ColorSlider-slider')
    }))));
});


//# sourceMappingURL=ColorSlider.cjs.map
