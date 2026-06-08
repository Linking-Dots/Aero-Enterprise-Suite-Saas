import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {useHover as $1mD4M$useHover} from "react-aria/useHover";
import {mergeProps as $1mD4M$mergeProps} from "react-aria/mergeProps";
import $1mD4M$react, {createContext as $1mD4M$createContext, forwardRef as $1mD4M$forwardRef} from "react";
import {useFocusRing as $1mD4M$useFocusRing} from "react-aria/useFocusRing";

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




const $3a442827418ebe87$export$f9c6924e160136d1 = /*#__PURE__*/ (0, $1mD4M$createContext)({});
const $3a442827418ebe87$export$eb2fcfdbd7ba97d4 = /*#__PURE__*/ (0, $1mD4M$forwardRef)(function Group(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $3a442827418ebe87$export$f9c6924e160136d1);
    let { isDisabled: isDisabled, isInvalid: isInvalid, isReadOnly: isReadOnly, onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    isDisabled ??= !!props['aria-disabled'] && props['aria-disabled'] !== 'false';
    isInvalid ??= !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $1mD4M$useHover)({
        onHoverStart: onHoverStart,
        onHoverChange: onHoverChange,
        onHoverEnd: onHoverEnd,
        isDisabled: isDisabled
    });
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $1mD4M$useFocusRing)({
        within: true
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            isHovered: isHovered,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled,
            isInvalid: isInvalid
        },
        defaultClassName: 'react-aria-Group'
    });
    return /*#__PURE__*/ (0, $1mD4M$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $1mD4M$mergeProps)(otherProps, focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        role: props.role ?? 'group',
        slot: props.slot ?? undefined,
        "data-focus-within": isFocused || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined,
        "data-invalid": isInvalid || undefined,
        "data-readonly": isReadOnly || undefined
    }, renderProps.children);
});


export {$3a442827418ebe87$export$f9c6924e160136d1 as GroupContext, $3a442827418ebe87$export$eb2fcfdbd7ba97d4 as Group};
//# sourceMappingURL=Group.mjs.map
