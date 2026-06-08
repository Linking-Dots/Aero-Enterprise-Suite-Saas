import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {SelectionIndicatorContext as $91fe5e721c7f36c1$export$c9549807523555e0} from "./SelectionIndicator.mjs";
import {ToggleGroupStateContext as $bc25b811ec97a172$export$a8a71863db173133} from "./ToggleButtonGroup.mjs";
import {useToggleButton as $2YxXO$useToggleButton} from "react-aria/useToggleButton";
import {filterDOMProps as $2YxXO$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $2YxXO$mergeProps} from "react-aria/mergeProps";
import $2YxXO$react, {createContext as $2YxXO$createContext, forwardRef as $2YxXO$forwardRef, useContext as $2YxXO$useContext} from "react";
import {useToggleState as $2YxXO$useToggleState} from "react-stately/useToggleState";
import {useFocusRing as $2YxXO$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $2YxXO$useHover} from "react-aria/useHover";
import {useToggleButtonGroupItem as $2YxXO$useToggleButtonGroupItem} from "react-aria/useToggleButtonGroup";

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










const $75afe627ae0cc0fa$export$43506d75ebd2e218 = /*#__PURE__*/ (0, $2YxXO$createContext)({});
const $75afe627ae0cc0fa$export$d2b052e7b4be1756 = /*#__PURE__*/ (0, $2YxXO$forwardRef)(function ToggleButton(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $75afe627ae0cc0fa$export$43506d75ebd2e218);
    let groupState = (0, $2YxXO$useContext)((0, $bc25b811ec97a172$export$a8a71863db173133));
    let state = (0, $2YxXO$useToggleState)(groupState && props.id != null ? {
        isSelected: groupState.selectedKeys.has(props.id),
        onChange (isSelected) {
            groupState.setSelected(props.id, isSelected);
        }
    } : props);
    let { buttonProps: buttonProps, isPressed: isPressed, isSelected: isSelected, isDisabled: isDisabled } = groupState && props.id != null ? (0, $2YxXO$useToggleButtonGroupItem)({
        ...props,
        id: props.id
    }, groupState, ref) : (0, $2YxXO$useToggleButton)({
        ...props,
        id: props.id != null ? String(props.id) : undefined
    }, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $2YxXO$useFocusRing)(props);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $2YxXO$useHover)({
        ...props,
        isDisabled: isDisabled
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        id: undefined,
        values: {
            isHovered: isHovered,
            isPressed: isPressed,
            isFocused: isFocused,
            isSelected: state.isSelected,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ToggleButton'
    });
    let DOMProps = (0, $2YxXO$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $2YxXO$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).button, {
        ...(0, $2YxXO$mergeProps)(DOMProps, renderProps, buttonProps, focusProps, hoverProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": isFocused || undefined,
        "data-disabled": isDisabled || undefined,
        "data-pressed": isPressed || undefined,
        "data-selected": isSelected || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, $2YxXO$react).createElement((0, $91fe5e721c7f36c1$export$c9549807523555e0).Provider, {
        value: {
            isSelected: isSelected
        }
    }, renderProps.children));
});


export {$75afe627ae0cc0fa$export$43506d75ebd2e218 as ToggleButtonContext, $75afe627ae0cc0fa$export$d2b052e7b4be1756 as ToggleButton};
//# sourceMappingURL=ToggleButton.mjs.map
