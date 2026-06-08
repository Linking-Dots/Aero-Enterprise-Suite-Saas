import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {useLink as $czt8L$useLink} from "react-aria/useLink";
import {filterDOMProps as $czt8L$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $czt8L$mergeProps} from "react-aria/mergeProps";
import $czt8L$react, {createContext as $czt8L$createContext, forwardRef as $czt8L$forwardRef} from "react";
import {useFocusRing as $czt8L$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $czt8L$useHover} from "react-aria/useHover";

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






const $984a1fc08f87e4f3$export$e2509388b49734e7 = /*#__PURE__*/ (0, $czt8L$createContext)(null);
const $984a1fc08f87e4f3$export$a6c7ac8248d6e38a = /*#__PURE__*/ (0, $czt8L$forwardRef)(function Link(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $984a1fc08f87e4f3$export$e2509388b49734e7);
    let elementType = props.href && !props.isDisabled ? 'a' : 'span';
    let { linkProps: linkProps, isPressed: isPressed } = (0, $czt8L$useLink)({
        ...props,
        elementType: elementType
    }, ref);
    let ElementType = (0, $7230ffa83bc0c2cf$export$df3a06d6289f983e)[elementType];
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $czt8L$useHover)(props);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $czt8L$useFocusRing)();
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-Link',
        values: {
            isCurrent: !!props['aria-current'],
            isDisabled: props.isDisabled || false,
            isPressed: isPressed,
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        }
    });
    let DOMProps = (0, $czt8L$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $czt8L$react).createElement(ElementType, {
        ref: ref,
        slot: props.slot || undefined,
        ...(0, $czt8L$mergeProps)(DOMProps, renderProps, linkProps, hoverProps, focusProps),
        "data-focused": isFocused || undefined,
        "data-hovered": isHovered || undefined,
        "data-pressed": isPressed || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-current": !!props['aria-current'] || undefined,
        "data-disabled": props.isDisabled || undefined
    }, renderProps.children);
});


export {$984a1fc08f87e4f3$export$e2509388b49734e7 as LinkContext, $984a1fc08f87e4f3$export$a6c7ac8248d6e38a as Link};
//# sourceMappingURL=Link.mjs.map
