import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {SelectionIndicatorContext as $0d6f83ad40839938$export$c9549807523555e0} from "./SelectionIndicator.js";
import {ToggleGroupStateContext as $e80299348b6d9861$export$a8a71863db173133} from "./ToggleButtonGroup.js";
import {useToggleButton as $jU0KW$useToggleButton} from "react-aria/useToggleButton";
import {filterDOMProps as $jU0KW$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $jU0KW$mergeProps} from "react-aria/mergeProps";
import $jU0KW$react, {createContext as $jU0KW$createContext, forwardRef as $jU0KW$forwardRef, useContext as $jU0KW$useContext} from "react";
import {useToggleState as $jU0KW$useToggleState} from "react-stately/useToggleState";
import {useFocusRing as $jU0KW$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $jU0KW$useHover} from "react-aria/useHover";
import {useToggleButtonGroupItem as $jU0KW$useToggleButtonGroupItem} from "react-aria/useToggleButtonGroup";

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










const $a38158b96b4eb9f9$export$43506d75ebd2e218 = /*#__PURE__*/ (0, $jU0KW$createContext)({});
const $a38158b96b4eb9f9$export$d2b052e7b4be1756 = /*#__PURE__*/ (0, $jU0KW$forwardRef)(function ToggleButton(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $a38158b96b4eb9f9$export$43506d75ebd2e218);
    let groupState = (0, $jU0KW$useContext)((0, $e80299348b6d9861$export$a8a71863db173133));
    let state = (0, $jU0KW$useToggleState)(groupState && props.id != null ? {
        isSelected: groupState.selectedKeys.has(props.id),
        onChange (isSelected) {
            groupState.setSelected(props.id, isSelected);
        }
    } : props);
    let { buttonProps: buttonProps, isPressed: isPressed, isSelected: isSelected, isDisabled: isDisabled } = groupState && props.id != null ? (0, $jU0KW$useToggleButtonGroupItem)({
        ...props,
        id: props.id
    }, groupState, ref) : (0, $jU0KW$useToggleButton)({
        ...props,
        id: props.id != null ? String(props.id) : undefined
    }, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $jU0KW$useFocusRing)(props);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $jU0KW$useHover)({
        ...props,
        isDisabled: isDisabled
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $jU0KW$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $jU0KW$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).button, {
        ...(0, $jU0KW$mergeProps)(DOMProps, renderProps, buttonProps, focusProps, hoverProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": isFocused || undefined,
        "data-disabled": isDisabled || undefined,
        "data-pressed": isPressed || undefined,
        "data-selected": isSelected || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, $jU0KW$react).createElement((0, $0d6f83ad40839938$export$c9549807523555e0).Provider, {
        value: {
            isSelected: isSelected
        }
    }, renderProps.children));
});


export {$a38158b96b4eb9f9$export$43506d75ebd2e218 as ToggleButtonContext, $a38158b96b4eb9f9$export$d2b052e7b4be1756 as ToggleButton};
//# sourceMappingURL=ToggleButton.js.map
