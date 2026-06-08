import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../toggle_vars.css";
import $cn7nU$toggle_vars_cssmjs from "../toggle_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useSwitch as $cn7nU$useSwitch} from "react-aria/useSwitch";
import {FocusRing as $cn7nU$FocusRing} from "react-aria/FocusRing";
import $cn7nU$react, {forwardRef as $cn7nU$forwardRef, useRef as $cn7nU$useRef} from "react";
import {useHover as $cn7nU$useHover} from "react-aria/useHover";
import {useToggleState as $cn7nU$useToggleState} from "react-stately/useToggleState";


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









const $28f2819121a8fb15$export$b5d5cf8927ab7262 = /*#__PURE__*/ (0, $cn7nU$forwardRef)(function Switch(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { isEmphasized: isEmphasized = false, isDisabled: isDisabled = false, autoFocus: autoFocus, children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $cn7nU$useHover)({
        isDisabled: isDisabled
    });
    let inputRef = (0, $cn7nU$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputRef);
    let state = (0, $cn7nU$useToggleState)(props);
    let { labelProps: labelProps, inputProps: inputProps } = (0, $cn7nU$useSwitch)(props, state, inputRef);
    return /*#__PURE__*/ (0, $cn7nU$react).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cn7nU$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch', {
            'spectrum-ToggleSwitch--quiet': !isEmphasized,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $cn7nU$react).createElement((0, $cn7nU$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cn7nU$toggle_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $cn7nU$react).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cn7nU$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch-input')
    })), /*#__PURE__*/ (0, $cn7nU$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cn7nU$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch-switch')
    }), children && /*#__PURE__*/ (0, $cn7nU$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cn7nU$toggle_vars_cssmjs))), 'spectrum-ToggleSwitch-label')
    }, children));
});


export {$28f2819121a8fb15$export$b5d5cf8927ab7262 as Switch};
//# sourceMappingURL=Switch.mjs.map
