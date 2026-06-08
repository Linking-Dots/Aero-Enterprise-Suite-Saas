var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../toggle_vars.css");
var $fa9e01bf189eadc9$exports = require("../toggle_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $icIga$reactariauseSwitch = require("react-aria/useSwitch");
var $icIga$reactariaFocusRing = require("react-aria/FocusRing");
var $icIga$react = require("react");
var $icIga$reactariauseHover = require("react-aria/useHover");
var $icIga$reactstatelyuseToggleState = require("react-stately/useToggleState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Switch", function () { return $c9565a090553a20d$export$b5d5cf8927ab7262; });
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









const $c9565a090553a20d$export$b5d5cf8927ab7262 = /*#__PURE__*/ (0, $icIga$react.forwardRef)(function Switch(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { isEmphasized: isEmphasized = false, isDisabled: isDisabled = false, autoFocus: autoFocus, children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $icIga$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let inputRef = (0, $icIga$react.useRef)(null);
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref, inputRef);
    let state = (0, $icIga$reactstatelyuseToggleState.useToggleState)(props);
    let { labelProps: labelProps, inputProps: inputProps } = (0, $icIga$reactariauseSwitch.useSwitch)(props, state, inputRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($icIga$react))).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($fa9e01bf189eadc9$exports))), 'spectrum-ToggleSwitch', {
            'spectrum-ToggleSwitch--quiet': !isEmphasized,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($icIga$react))).createElement((0, $icIga$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($fa9e01bf189eadc9$exports))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($icIga$react))).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($fa9e01bf189eadc9$exports))), 'spectrum-ToggleSwitch-input')
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($icIga$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($fa9e01bf189eadc9$exports))), 'spectrum-ToggleSwitch-switch')
    }), children && /*#__PURE__*/ (0, ($parcel$interopDefault($icIga$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($fa9e01bf189eadc9$exports))), 'spectrum-ToggleSwitch-label')
    }, children));
});


//# sourceMappingURL=Switch.cjs.map
