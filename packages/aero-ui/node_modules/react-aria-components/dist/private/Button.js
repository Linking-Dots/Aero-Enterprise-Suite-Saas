import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {ProgressBarContext as $71551be8b98e6856$export$e9f3bf65a26ce129} from "./ProgressBar.js";
import {announce as $tkAfK$announce} from "react-aria/private/live-announcer/LiveAnnouncer";
import {useButton as $tkAfK$useButton} from "react-aria/useButton";
import {createHideableComponent as $tkAfK$createHideableComponent} from "react-aria/private/collections/Hidden";
import {filterDOMProps as $tkAfK$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $tkAfK$mergeProps} from "react-aria/mergeProps";
import $tkAfK$react, {createContext as $tkAfK$createContext, useRef as $tkAfK$useRef, useEffect as $tkAfK$useEffect} from "react";
import {useFocusRing as $tkAfK$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $tkAfK$useHover} from "react-aria/useHover";
import {useId as $tkAfK$useId} from "react-aria/useId";

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










const $fc203795b9b363cd$export$24d547caef80ccd1 = /*#__PURE__*/ (0, $tkAfK$createContext)({});
const $fc203795b9b363cd$export$353f5b6fc5456de1 = /*#__PURE__*/ (0, $tkAfK$createHideableComponent)(function Button(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $fc203795b9b363cd$export$24d547caef80ccd1);
    let ctx = props;
    let { isPending: isPending } = ctx;
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $tkAfK$useButton)(props, ref);
    buttonProps = $fc203795b9b363cd$var$useDisableInteractions(buttonProps, isPending);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $tkAfK$useFocusRing)(props);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $tkAfK$useHover)({
        ...props,
        isDisabled: props.isDisabled || isPending
    });
    let renderValues = {
        isHovered: isHovered,
        isPressed: (ctx.isPressed || isPressed) && !isPending,
        isFocused: isFocused,
        isFocusVisible: isFocusVisible,
        isDisabled: props.isDisabled || false,
        isPending: isPending !== null && isPending !== void 0 ? isPending : false
    };
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: renderValues,
        defaultClassName: 'react-aria-Button'
    });
    let buttonId = (0, $tkAfK$useId)(buttonProps.id);
    let progressId = (0, $tkAfK$useId)();
    let ariaLabelledby = buttonProps['aria-labelledby'];
    if (isPending) {
        // aria-labelledby wins over aria-label
        // https://www.w3.org/TR/accname-1.2/#computation-steps
        if (ariaLabelledby) ariaLabelledby = `${ariaLabelledby} ${progressId}`;
        else if (buttonProps['aria-label']) ariaLabelledby = `${buttonId} ${progressId}`;
    }
    let wasPending = (0, $tkAfK$useRef)(isPending);
    (0, $tkAfK$useEffect)(()=>{
        let message = {
            'aria-labelledby': ariaLabelledby || buttonId
        };
        if (!wasPending.current && isFocused && isPending) (0, $tkAfK$announce)(message, 'assertive');
        else if (wasPending.current && isFocused && !isPending) (0, $tkAfK$announce)(message, 'assertive');
        wasPending.current = isPending;
    }, [
        isPending,
        isFocused,
        ariaLabelledby,
        buttonId
    ]);
    let DOMProps = (0, $tkAfK$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $tkAfK$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).button, {
        ...(0, $tkAfK$mergeProps)(DOMProps, renderProps, buttonProps, focusProps, hoverProps),
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
    }, /*#__PURE__*/ (0, $tkAfK$react).createElement((0, $71551be8b98e6856$export$e9f3bf65a26ce129).Provider, {
        value: {
            id: progressId
        }
    }, renderProps.children));
});
// Events to preserve when isPending is true (for tooltips and other overlays)
const $fc203795b9b363cd$var$PRESERVED_EVENT_PATTERN = /Focus|Blur|Hover|Pointer(Enter|Leave|Over|Out)|Mouse(Enter|Leave|Over|Out)/;
function $fc203795b9b363cd$var$useDisableInteractions(props, isPending) {
    if (isPending) {
        for(const key in props)if (key.startsWith('on') && !$fc203795b9b363cd$var$PRESERVED_EVENT_PATTERN.test(key)) props[key] = undefined;
        props.href = undefined;
        props.target = undefined;
    }
    return props;
}


export {$fc203795b9b363cd$export$24d547caef80ccd1 as ButtonContext, $fc203795b9b363cd$export$353f5b6fc5456de1 as Button};
//# sourceMappingURL=Button.js.map
