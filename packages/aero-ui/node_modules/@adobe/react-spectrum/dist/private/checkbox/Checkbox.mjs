import {CheckboxGroupContext as $f8719a07416b8330$export$baf37c4be89255b8} from "./context.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../checkbox_vars.css";
import $bhh5Z$checkbox_vars_cssmjs from "../checkbox_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useCheckbox as $bhh5Z$useCheckbox} from "react-aria/useCheckbox";
import {CheckboxContext as $bhh5Z$CheckboxContext} from "react-aria-components/Checkbox";
import $bhh5Z$spectrumiconsuiCheckmarkSmall from "@spectrum-icons/ui/CheckmarkSmall";
import $bhh5Z$spectrumiconsuiDashSmall from "@spectrum-icons/ui/DashSmall";
import {FocusRing as $bhh5Z$FocusRing} from "react-aria/FocusRing";
import $bhh5Z$react, {forwardRef as $bhh5Z$forwardRef, useRef as $bhh5Z$useRef, useContext as $bhh5Z$useContext} from "react";
import {useCheckboxGroupItem as $bhh5Z$useCheckboxGroupItem} from "react-aria/useCheckboxGroup";
import {useContextProps as $bhh5Z$useContextProps} from "react-aria-components/slots";
import {useHover as $bhh5Z$useHover} from "react-aria/useHover";
import {useToggleState as $bhh5Z$useToggleState} from "react-stately/useToggleState";


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
















const $b50e47f9c64ebdde$export$48513f6b9f8ce62d = /*#__PURE__*/ (0, $bhh5Z$forwardRef)(function Checkbox(props, ref) {
    let originalProps = props;
    let inputRef = (0, $bhh5Z$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputRef);
    [props, domRef] = (0, $bhh5Z$useContextProps)(props, domRef, (0, $bhh5Z$CheckboxContext));
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let { isIndeterminate: isIndeterminate = false, isEmphasized: isEmphasized = false, autoFocus: autoFocus, children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    // Swap hooks depending on whether this checkbox is inside a CheckboxGroup.
    // This is a bit unorthodox. Typically, hooks cannot be called in a conditional,
    // but since the checkbox won't move in and out of a group, it should be safe.
    let groupState = (0, $bhh5Z$useContext)((0, $f8719a07416b8330$export$baf37c4be89255b8));
    let { labelProps: labelProps, inputProps: inputProps, isInvalid: isInvalid, isDisabled: isDisabled } = groupState ? (0, $bhh5Z$useCheckboxGroupItem)({
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
    }, groupState, inputRef) : (0, $bhh5Z$useCheckbox)(props, (0, $bhh5Z$useToggleState)(props), inputRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $bhh5Z$useHover)({
        isDisabled: isDisabled
    });
    let markIcon = isIndeterminate ? /*#__PURE__*/ (0, $bhh5Z$react).createElement((0, $bhh5Z$spectrumiconsuiDashSmall), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'spectrum-Checkbox-partialCheckmark')
    }) : /*#__PURE__*/ (0, $bhh5Z$react).createElement((0, $bhh5Z$spectrumiconsuiCheckmarkSmall), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'spectrum-Checkbox-checkmark')
    });
    if (groupState && process.env.NODE_ENV !== 'production') {
        for (let key of [
            'isSelected',
            'defaultSelected',
            'isEmphasized'
        ])if (originalProps[key] != null) console.warn(`${key} is unsupported on individual <Checkbox> elements within a <CheckboxGroup>. Please apply these props to the group instead.`);
        if (props.value == null) console.warn('A <Checkbox> element within a <CheckboxGroup> requires a `value` property.');
    }
    return /*#__PURE__*/ (0, $bhh5Z$react).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'spectrum-Checkbox', {
            'is-checked': inputProps.checked,
            'is-indeterminate': isIndeterminate,
            'spectrum-Checkbox--quiet': !isEmphasized,
            'is-invalid': isInvalid,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $bhh5Z$react).createElement((0, $bhh5Z$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, $bhh5Z$react).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'spectrum-Checkbox-input')
    })), /*#__PURE__*/ (0, $bhh5Z$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'spectrum-Checkbox-box')
    }, markIcon), children && /*#__PURE__*/ (0, $bhh5Z$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bhh5Z$checkbox_vars_cssmjs))), 'spectrum-Checkbox-label')
    }, children));
});


export {$b50e47f9c64ebdde$export$48513f6b9f8ce62d as Checkbox};
//# sourceMappingURL=Checkbox.mjs.map
