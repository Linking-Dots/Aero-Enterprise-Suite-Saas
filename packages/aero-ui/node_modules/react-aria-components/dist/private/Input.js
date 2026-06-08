import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {createHideableComponent as $l0qp7$createHideableComponent} from "react-aria/private/collections/Hidden";
import {mergeProps as $l0qp7$mergeProps} from "react-aria/mergeProps";
import $l0qp7$react, {createContext as $l0qp7$createContext} from "react";
import {useFocusRing as $l0qp7$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $l0qp7$useHover} from "react-aria/useHover";

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





const $d8e7992b5f7739ce$export$37fb8590cf2c088c = /*#__PURE__*/ (0, $l0qp7$createContext)({});
let $d8e7992b5f7739ce$var$filterHoverProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $d8e7992b5f7739ce$export$f5b8910cec6cf069 = /*#__PURE__*/ (0, $l0qp7$createHideableComponent)(function Input(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $d8e7992b5f7739ce$export$37fb8590cf2c088c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $l0qp7$useHover)({
        ...props,
        isDisabled: props.disabled
    });
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $l0qp7$useFocusRing)({
        isTextInput: true,
        autoFocus: props.autoFocus
    });
    let isInvalid = !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.disabled || false,
            isInvalid: isInvalid
        },
        defaultClassName: 'react-aria-Input'
    });
    return /*#__PURE__*/ (0, $l0qp7$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).input, {
        ...(0, $l0qp7$mergeProps)($d8e7992b5f7739ce$var$filterHoverProps(props), focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-disabled": props.disabled || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-invalid": isInvalid || undefined
    });
});


export {$d8e7992b5f7739ce$export$37fb8590cf2c088c as InputContext, $d8e7992b5f7739ce$export$f5b8910cec6cf069 as Input};
//# sourceMappingURL=Input.js.map
