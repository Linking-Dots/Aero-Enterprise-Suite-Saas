import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {createHideableComponent as $1aA6s$createHideableComponent} from "react-aria/private/collections/Hidden";
import {mergeProps as $1aA6s$mergeProps} from "react-aria/mergeProps";
import $1aA6s$react, {createContext as $1aA6s$createContext} from "react";
import {useFocusRing as $1aA6s$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $1aA6s$useHover} from "react-aria/useHover";

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





const $41fb335299a4a39e$export$37fb8590cf2c088c = /*#__PURE__*/ (0, $1aA6s$createContext)({});
let $41fb335299a4a39e$var$filterHoverProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $41fb335299a4a39e$export$f5b8910cec6cf069 = /*#__PURE__*/ (0, $1aA6s$createHideableComponent)(function Input(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $41fb335299a4a39e$export$37fb8590cf2c088c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1aA6s$useHover)({
        ...props,
        isDisabled: props.disabled
    });
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $1aA6s$useFocusRing)({
        isTextInput: true,
        autoFocus: props.autoFocus
    });
    let isInvalid = !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $1aA6s$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).input, {
        ...(0, $1aA6s$mergeProps)($41fb335299a4a39e$var$filterHoverProps(props), focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-disabled": props.disabled || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-invalid": isInvalid || undefined
    });
});


export {$41fb335299a4a39e$export$37fb8590cf2c088c as InputContext, $41fb335299a4a39e$export$f5b8910cec6cf069 as Input};
//# sourceMappingURL=Input.mjs.map
