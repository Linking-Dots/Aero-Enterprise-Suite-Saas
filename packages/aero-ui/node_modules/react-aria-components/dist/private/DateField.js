import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import {Group as $2e357e4f16c05be6$export$eb2fcfdbd7ba97d4, GroupContext as $2e357e4f16c05be6$export$f9c6924e160136d1} from "./Group.js";
import {HiddenDateInput as $7133f72f3bca7442$export$eefa3e19139f00f3} from "./HiddenDateInput.js";
import {Input as $d8e7992b5f7739ce$export$f5b8910cec6cf069, InputContext as $d8e7992b5f7739ce$export$37fb8590cf2c088c} from "./Input.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useDateField as $4T6Jt$useDateField, useDateSegment as $4T6Jt$useDateSegment} from "react-aria/useDateField";
import {useTimeField as $4T6Jt$useTimeField} from "react-aria/useTimeField";
import {createCalendar as $4T6Jt$createCalendar} from "@internationalized/date";
import {useDateFieldState as $4T6Jt$useDateFieldState} from "react-stately/useDateFieldState";
import {filterDOMProps as $4T6Jt$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $4T6Jt$mergeProps} from "react-aria/mergeProps";
import $4T6Jt$react, {createContext as $4T6Jt$createContext, forwardRef as $4T6Jt$forwardRef, useRef as $4T6Jt$useRef, useContext as $4T6Jt$useContext, cloneElement as $4T6Jt$cloneElement} from "react";
import {useTimeFieldState as $4T6Jt$useTimeFieldState} from "react-stately/useTimeFieldState";
import {useFocusRing as $4T6Jt$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $4T6Jt$useHover} from "react-aria/useHover";
import {useLocale as $4T6Jt$useLocale} from "react-aria/I18nProvider";
import {useObjectRef as $4T6Jt$useObjectRef} from "react-aria/useObjectRef";

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



















const $ebd46e18226db4de$export$7b3e670c86da5fe8 = /*#__PURE__*/ (0, $4T6Jt$createContext)(null);
const $ebd46e18226db4de$export$8e17ddc448e87c1e = /*#__PURE__*/ (0, $4T6Jt$createContext)(null);
const $ebd46e18226db4de$export$3b08bebcf796eea0 = /*#__PURE__*/ (0, $4T6Jt$createContext)(null);
const $ebd46e18226db4de$export$5d8dc44abd10a920 = /*#__PURE__*/ (0, $4T6Jt$createContext)(null);
const $ebd46e18226db4de$export$d9781c7894a82487 = /*#__PURE__*/ (0, $4T6Jt$forwardRef)(function DateField(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $ebd46e18226db4de$export$7b3e670c86da5fe8);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let { locale: locale } = (0, $4T6Jt$useLocale)();
    let state = (0, $4T6Jt$useDateFieldState)({
        ...props,
        locale: locale,
        createCalendar: $4T6Jt$createCalendar,
        validationBehavior: validationBehavior
    });
    let fieldRef = (0, $4T6Jt$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let inputRef = (0, $4T6Jt$useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $4T6Jt$useDateField)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        inputRef: inputRef,
        validationBehavior: validationBehavior
    }, state, fieldRef);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        values: {
            state: state,
            isInvalid: state.isInvalid,
            isDisabled: state.isDisabled,
            isReadOnly: state.isReadOnly,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DateField'
    });
    let DOMProps = (0, $4T6Jt$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $ebd46e18226db4de$export$3b08bebcf796eea0,
                state
            ],
            [
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ],
            [
                (0, $d8e7992b5f7739ce$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $6567560e1d9cc847$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }), /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $7133f72f3bca7442$export$eefa3e19139f00f3), {
        autoComplete: props.autoComplete,
        name: props.name,
        isDisabled: props.isDisabled,
        state: state
    }));
});
const $ebd46e18226db4de$export$5eaee2322dd727eb = /*#__PURE__*/ (0, $4T6Jt$forwardRef)(function TimeField(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $ebd46e18226db4de$export$8e17ddc448e87c1e);
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let { locale: locale } = (0, $4T6Jt$useLocale)();
    let state = (0, $4T6Jt$useTimeFieldState)({
        ...props,
        locale: locale,
        validationBehavior: validationBehavior
    });
    let fieldRef = (0, $4T6Jt$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let inputRef = (0, $4T6Jt$useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $4T6Jt$useTimeField)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        inputRef: inputRef,
        validationBehavior: validationBehavior
    }, state, fieldRef);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isInvalid: state.isInvalid,
            isDisabled: state.isDisabled,
            isReadOnly: state.isReadOnly,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-TimeField'
    });
    let DOMProps = (0, $4T6Jt$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $ebd46e18226db4de$export$5d8dc44abd10a920,
                state
            ],
            [
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ],
            [
                (0, $d8e7992b5f7739ce$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $6567560e1d9cc847$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }));
});
const $ebd46e18226db4de$export$7edc06cf1783b30f = /*#__PURE__*/ (0, $4T6Jt$forwardRef)(function DateInput(props, ref) {
    // If state is provided by DateField/TimeField, just render.
    // Otherwise (e.g. in DatePicker), we need to call hooks and create state ourselves.
    let dateFieldState = (0, $4T6Jt$useContext)($ebd46e18226db4de$export$3b08bebcf796eea0);
    let timeFieldState = (0, $4T6Jt$useContext)($ebd46e18226db4de$export$5d8dc44abd10a920);
    return dateFieldState || timeFieldState ? /*#__PURE__*/ (0, $4T6Jt$react).createElement($ebd46e18226db4de$var$DateInputInner, {
        ...props,
        ref: ref
    }) : /*#__PURE__*/ (0, $4T6Jt$react).createElement($ebd46e18226db4de$var$DateInputStandalone, {
        ...props,
        ref: ref
    });
});
const $ebd46e18226db4de$var$DateInputStandalone = /*#__PURE__*/ (0, $4T6Jt$forwardRef)((props, ref)=>{
    let [dateFieldProps, fieldRef] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)({
        slot: props.slot
    }, ref, $ebd46e18226db4de$export$7b3e670c86da5fe8);
    let { locale: locale } = (0, $4T6Jt$useLocale)();
    let state = (0, $4T6Jt$useDateFieldState)({
        ...dateFieldProps,
        locale: locale,
        createCalendar: $4T6Jt$createCalendar
    });
    let inputRef = (0, $4T6Jt$useRef)(null);
    let { fieldProps: fieldProps, inputProps: inputProps } = (0, $4T6Jt$useDateField)({
        ...dateFieldProps,
        inputRef: inputRef
    }, state, fieldRef);
    return /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $ebd46e18226db4de$export$3b08bebcf796eea0,
                state
            ],
            [
                (0, $d8e7992b5f7739ce$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $2e357e4f16c05be6$export$f9c6924e160136d1),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $4T6Jt$react).createElement($ebd46e18226db4de$var$DateInputInner, props));
});
const $ebd46e18226db4de$var$DateInputInner = /*#__PURE__*/ (0, $4T6Jt$forwardRef)((props, ref)=>{
    let { className: className, children: children } = props;
    let dateFieldState = (0, $4T6Jt$useContext)($ebd46e18226db4de$export$3b08bebcf796eea0);
    let timeFieldState = (0, $4T6Jt$useContext)($ebd46e18226db4de$export$5d8dc44abd10a920);
    let state = dateFieldState !== null && dateFieldState !== void 0 ? dateFieldState : timeFieldState;
    return /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $4T6Jt$react).Fragment, null, /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $2e357e4f16c05be6$export$eb2fcfdbd7ba97d4), {
        ...props,
        ref: ref,
        slot: props.slot || undefined,
        className: className !== null && className !== void 0 ? className : 'react-aria-DateInput',
        isReadOnly: state.isReadOnly,
        isInvalid: state.isInvalid,
        isDisabled: state.isDisabled
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $4T6Jt$cloneElement)(children(segment), {
            key: i
        }))), /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $d8e7992b5f7739ce$export$f5b8910cec6cf069), {
        className: ""
    }));
});
const $ebd46e18226db4de$export$336ab7fa954c4b5f = /*#__PURE__*/ (0, $4T6Jt$forwardRef)(function DateSegment({ segment: segment, ...otherProps }, ref) {
    let dateFieldState = (0, $4T6Jt$useContext)($ebd46e18226db4de$export$3b08bebcf796eea0);
    let timeFieldState = (0, $4T6Jt$useContext)($ebd46e18226db4de$export$5d8dc44abd10a920);
    let state = dateFieldState !== null && dateFieldState !== void 0 ? dateFieldState : timeFieldState;
    let domRef = (0, $4T6Jt$useObjectRef)(ref);
    let { segmentProps: segmentProps } = (0, $4T6Jt$useDateSegment)(segment, state, domRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $4T6Jt$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $4T6Jt$useHover)({
        ...otherProps,
        isDisabled: state.isDisabled || segment.type === 'literal'
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...otherProps,
        values: {
            ...segment,
            isReadOnly: state.isReadOnly,
            isInvalid: state.isInvalid,
            isDisabled: state.isDisabled,
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        },
        defaultChildren: segment.text,
        defaultClassName: 'react-aria-DateSegment'
    });
    return /*#__PURE__*/ (0, $4T6Jt$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).span, {
        ...(0, $4T6Jt$mergeProps)((0, $4T6Jt$filterDOMProps)(otherProps, {
            global: true
        }), segmentProps, focusProps, hoverProps),
        ...renderProps,
        style: segmentProps.style,
        ref: domRef,
        "data-placeholder": segment.isPlaceholder || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-type": segment.type,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    });
});


export {$ebd46e18226db4de$export$7b3e670c86da5fe8 as DateFieldContext, $ebd46e18226db4de$export$8e17ddc448e87c1e as TimeFieldContext, $ebd46e18226db4de$export$3b08bebcf796eea0 as DateFieldStateContext, $ebd46e18226db4de$export$5d8dc44abd10a920 as TimeFieldStateContext, $ebd46e18226db4de$export$d9781c7894a82487 as DateField, $ebd46e18226db4de$export$5eaee2322dd727eb as TimeField, $ebd46e18226db4de$export$7edc06cf1783b30f as DateInput, $ebd46e18226db4de$export$336ab7fa954c4b5f as DateSegment};
//# sourceMappingURL=DateField.js.map
