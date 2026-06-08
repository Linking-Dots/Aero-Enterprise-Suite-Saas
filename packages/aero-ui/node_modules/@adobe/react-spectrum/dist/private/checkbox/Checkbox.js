import {CheckboxGroupContext as $b27972722bd47f5e$export$baf37c4be89255b8} from "./context.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../checkbox_vars.css";
import $fJ5Lq$checkbox_vars_cssmjs from "../checkbox_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useCheckbox as $fJ5Lq$useCheckbox} from "react-aria/useCheckbox";
import {CheckboxContext as $fJ5Lq$CheckboxContext} from "react-aria-components/Checkbox";
import $fJ5Lq$spectrumiconsuiCheckmarkSmall from "@spectrum-icons/ui/CheckmarkSmall";
import $fJ5Lq$spectrumiconsuiDashSmall from "@spectrum-icons/ui/DashSmall";
import {FocusRing as $fJ5Lq$FocusRing} from "react-aria/FocusRing";
import $fJ5Lq$react, {forwardRef as $fJ5Lq$forwardRef, useRef as $fJ5Lq$useRef, useContext as $fJ5Lq$useContext} from "react";
import {useCheckboxGroupItem as $fJ5Lq$useCheckboxGroupItem} from "react-aria/useCheckboxGroup";
import {useContextProps as $fJ5Lq$useContextProps} from "react-aria-components/slots";
import {useHover as $fJ5Lq$useHover} from "react-aria/useHover";
import {useToggleState as $fJ5Lq$useToggleState} from "react-stately/useToggleState";


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
















const $986e1e93e04146a6$export$48513f6b9f8ce62d = /*#__PURE__*/ (0, $fJ5Lq$forwardRef)(function Checkbox(props, ref) {
    let originalProps = props;
    let inputRef = (0, $fJ5Lq$useRef)(null);
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref, inputRef);
    [props, domRef] = (0, $fJ5Lq$useContextProps)(props, domRef, (0, $fJ5Lq$CheckboxContext));
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let { isIndeterminate: isIndeterminate = false, isEmphasized: isEmphasized = false, autoFocus: autoFocus, children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    // Swap hooks depending on whether this checkbox is inside a CheckboxGroup.
    // This is a bit unorthodox. Typically, hooks cannot be called in a conditional,
    // but since the checkbox won't move in and out of a group, it should be safe.
    let groupState = (0, $fJ5Lq$useContext)((0, $b27972722bd47f5e$export$baf37c4be89255b8));
    let { labelProps: labelProps, inputProps: inputProps, isInvalid: isInvalid, isDisabled: isDisabled } = groupState ? (0, $fJ5Lq$useCheckboxGroupItem)({
        ...props,
        // Value is optional for standalone checkboxes, but required for CheckboxGroup items;
        // it's passed explicitly here to avoid typescript error (requires ignore).
        // @ts-ignore
        value: props.value,
        // Only pass isRequired and validationState to react-aria if they came from
        // the props for this individual checkbox, and not from the group via context.
        isRequired: originalProps.isRequired,
        validationState: originalProps.validationState,
        isInvalid: originalProps.isInvalid
    }, groupState, inputRef) : (0, $fJ5Lq$useCheckbox)(props, (0, $fJ5Lq$useToggleState)(props), inputRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $fJ5Lq$useHover)({
        isDisabled: isDisabled
    });
    let markIcon = isIndeterminate ? /*#__PURE__*/ (0, $fJ5Lq$react).createElement((0, $fJ5Lq$spectrumiconsuiDashSmall), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'spectrum-Checkbox-partialCheckmark')
    }) : /*#__PURE__*/ (0, $fJ5Lq$react).createElement((0, $fJ5Lq$spectrumiconsuiCheckmarkSmall), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'spectrum-Checkbox-checkmark')
    });
    if (groupState && process.env.NODE_ENV !== 'production') {
        for (let key of [
            'isSelected',
            'defaultSelected',
            'isEmphasized'
        ])if (originalProps[key] != null) console.warn(`${key} is unsupported on individual <Checkbox> elements within a <CheckboxGroup>. Please apply these props to the group instead.`);
        if (props.value == null) console.warn('A <Checkbox> element within a <CheckboxGroup> requires a `value` property.');
    }
    return /*#__PURE__*/ (0, $fJ5Lq$react).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'spectrum-Checkbox', {
            'is-checked': inputProps.checked,
            'is-indeterminate': isIndeterminate,
            'spectrum-Checkbox--quiet': !isEmphasized,
            'is-invalid': isInvalid,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $fJ5Lq$react).createElement((0, $fJ5Lq$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $fJ5Lq$react).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'spectrum-Checkbox-input')
    })), /*#__PURE__*/ (0, $fJ5Lq$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'spectrum-Checkbox-box')
    }, markIcon), children && /*#__PURE__*/ (0, $fJ5Lq$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fJ5Lq$checkbox_vars_cssmjs))), 'spectrum-Checkbox-label')
    }, children));
});


export {$986e1e93e04146a6$export$48513f6b9f8ce62d as Checkbox};
//# sourceMappingURL=Checkbox.js.map
