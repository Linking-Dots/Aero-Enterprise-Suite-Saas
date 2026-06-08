import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {ProgressBarContext as $6c0095e7e99364f2$export$e9f3bf65a26ce129} from "./ProgressBar.mjs";
import {announce as $9s8kH$announce} from "react-aria/private/live-announcer/LiveAnnouncer";
import {useButton as $9s8kH$useButton} from "react-aria/useButton";
import {createHideableComponent as $9s8kH$createHideableComponent} from "react-aria/private/collections/Hidden";
import {filterDOMProps as $9s8kH$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $9s8kH$mergeProps} from "react-aria/mergeProps";
import $9s8kH$react, {createContext as $9s8kH$createContext, useRef as $9s8kH$useRef, useEffect as $9s8kH$useEffect} from "react";
import {useFocusRing as $9s8kH$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $9s8kH$useHover} from "react-aria/useHover";
import {useId as $9s8kH$useId} from "react-aria/useId";

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










const $7705c033048f6da7$export$24d547caef80ccd1 = /*#__PURE__*/ (0, $9s8kH$createContext)({});
const $7705c033048f6da7$export$353f5b6fc5456de1 = /*#__PURE__*/ (0, $9s8kH$createHideableComponent)(function Button(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $7705c033048f6da7$export$24d547caef80ccd1);
    let ctx = props;
    let { isPending: isPending } = ctx;
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $9s8kH$useButton)(props, ref);
    buttonProps = $7705c033048f6da7$var$useDisableInteractions(buttonProps, isPending);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $9s8kH$useFocusRing)(props);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $9s8kH$useHover)({
        ...props,
        isDisabled: props.isDisabled || isPending
    });
    let renderValues = {
        isHovered: isHovered,
        isPressed: (ctx.isPressed || isPressed) && !isPending,
        isFocused: isFocused,
        isFocusVisible: isFocusVisible,
        isDisabled: props.isDisabled || false,
        isPending: isPending ?? false
    };
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: renderValues,
        defaultClassName: 'react-aria-Button'
    });
    let buttonId = (0, $9s8kH$useId)(buttonProps.id);
    let progressId = (0, $9s8kH$useId)();
    let ariaLabelledby = buttonProps['aria-labelledby'];
    if (isPending) {
        // aria-labelledby wins over aria-label
        // https://www.w3.org/TR/accname-1.2/#computation-steps
        if (ariaLabelledby) ariaLabelledby = `${ariaLabelledby} ${progressId}`;
        else if (buttonProps['aria-label']) ariaLabelledby = `${buttonId} ${progressId}`;
    }
    let wasPending = (0, $9s8kH$useRef)(isPending);
    (0, $9s8kH$useEffect)(()=>{
        let message = {
            'aria-labelledby': ariaLabelledby || buttonId
        };
        if (!wasPending.current && isFocused && isPending) (0, $9s8kH$announce)(message, 'assertive');
        else if (wasPending.current && isFocused && !isPending) (0, $9s8kH$announce)(message, 'assertive');
        wasPending.current = isPending;
    }, [
        isPending,
        isFocused,
        ariaLabelledby,
        buttonId
    ]);
    let DOMProps = (0, $9s8kH$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $9s8kH$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).button, {
        ...(0, $9s8kH$mergeProps)(DOMProps, renderProps, buttonProps, focusProps, hoverProps),
        // When the button is in a pending state, we want to stop implicit form submission (ie. when the user presses enter on a text input).
        // We do this by changing the button's type to button.
        type: buttonProps.type === 'submit' && isPending ? 'button' : buttonProps.type,
        id: buttonId,
        ref: ref,
        "aria-labelledby": ariaLabelledby,
        slot: props.slot || undefined,
        "aria-disabled": isPending ? 'true' : buttonProps['aria-disabled'],
        "data-disabled": props.isDisabled || undefined,
        "data-pressed": renderValues.isPressed || undefined,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-pending": isPending || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, $9s8kH$react).createElement((0, $6c0095e7e99364f2$export$e9f3bf65a26ce129).Provider, {
        value: {
            id: progressId
        }
    }, renderProps.children));
});
// Events to preserve when isPending is true (for tooltips and other overlays)
const $7705c033048f6da7$var$PRESERVED_EVENT_PATTERN = /Focus|Blur|Hover|Pointer(Enter|Leave|Over|Out)|Mouse(Enter|Leave|Over|Out)/;
function $7705c033048f6da7$var$useDisableInteractions(props, isPending) {
    if (isPending) {
        for(const key in props)if (key.startsWith('on') && !$7705c033048f6da7$var$PRESERVED_EVENT_PATTERN.test(key)) props[key] = undefined;
        props.href = undefined;
        props.target = undefined;
    }
    return props;
}


export {$7705c033048f6da7$export$24d547caef80ccd1 as ButtonContext, $7705c033048f6da7$export$353f5b6fc5456de1 as Button};
//# sourceMappingURL=Button.mjs.map
