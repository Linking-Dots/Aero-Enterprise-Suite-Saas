import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {Group as $3a442827418ebe87$export$eb2fcfdbd7ba97d4, GroupContext as $3a442827418ebe87$export$f9c6924e160136d1} from "./Group.mjs";
import {HiddenDateInput as $5a15223dacad897a$export$eefa3e19139f00f3} from "./HiddenDateInput.mjs";
import {Input as $41fb335299a4a39e$export$f5b8910cec6cf069, InputContext as $41fb335299a4a39e$export$37fb8590cf2c088c} from "./Input.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useDateField as $lX0XJ$useDateField, useDateSegment as $lX0XJ$useDateSegment} from "react-aria/useDateField";
import {useTimeField as $lX0XJ$useTimeField} from "react-aria/useTimeField";
import {createCalendar as $lX0XJ$createCalendar} from "@internationalized/date";
import {useDateFieldState as $lX0XJ$useDateFieldState} from "react-stately/useDateFieldState";
import {filterDOMProps as $lX0XJ$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $lX0XJ$mergeProps} from "react-aria/mergeProps";
import $lX0XJ$react, {createContext as $lX0XJ$createContext, forwardRef as $lX0XJ$forwardRef, useRef as $lX0XJ$useRef, useContext as $lX0XJ$useContext, cloneElement as $lX0XJ$cloneElement} from "react";
import {useTimeFieldState as $lX0XJ$useTimeFieldState} from "react-stately/useTimeFieldState";
import {useFocusRing as $lX0XJ$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $lX0XJ$useHover} from "react-aria/useHover";
import {useLocale as $lX0XJ$useLocale} from "react-aria/I18nProvider";
import {useObjectRef as $lX0XJ$useObjectRef} from "react-aria/useObjectRef";

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



















const $5400c097f4765e59$export$7b3e670c86da5fe8 = /*#__PURE__*/ (0, $lX0XJ$createContext)(null);
const $5400c097f4765e59$export$8e17ddc448e87c1e = /*#__PURE__*/ (0, $lX0XJ$createContext)(null);
const $5400c097f4765e59$export$3b08bebcf796eea0 = /*#__PURE__*/ (0, $lX0XJ$createContext)(null);
const $5400c097f4765e59$export$5d8dc44abd10a920 = /*#__PURE__*/ (0, $lX0XJ$createContext)(null);
const $5400c097f4765e59$export$d9781c7894a82487 = /*#__PURE__*/ (0, $lX0XJ$forwardRef)(function DateField(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $5400c097f4765e59$export$7b3e670c86da5fe8);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let { locale: locale } = (0, $lX0XJ$useLocale)();
    let state = (0, $lX0XJ$useDateFieldState)({
        ...props,
        locale: locale,
        createCalendar: $lX0XJ$createCalendar,
        validationBehavior: validationBehavior
    });
    let fieldRef = (0, $lX0XJ$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let inputRef = (0, $lX0XJ$useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $lX0XJ$useDateField)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        inputRef: inputRef,
        validationBehavior: validationBehavior
    }, state, fieldRef);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        values: {
            state: state,
            isInvalid: state.isInvalid,
            isDisabled: state.isDisabled,
            isReadOnly: state.isReadOnly,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DateField'
    });
    let DOMProps = (0, $lX0XJ$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $5400c097f4765e59$export$3b08bebcf796eea0,
                state
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ],
            [
                (0, $41fb335299a4a39e$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $1f3c3b1a70cec653$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }), /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $5a15223dacad897a$export$eefa3e19139f00f3), {
        autoComplete: props.autoComplete,
        name: props.name,
        isDisabled: props.isDisabled,
        state: state
    }));
});
const $5400c097f4765e59$export$5eaee2322dd727eb = /*#__PURE__*/ (0, $lX0XJ$forwardRef)(function TimeField(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $5400c097f4765e59$export$8e17ddc448e87c1e);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let { locale: locale } = (0, $lX0XJ$useLocale)();
    let state = (0, $lX0XJ$useTimeFieldState)({
        ...props,
        locale: locale,
        validationBehavior: validationBehavior
    });
    let fieldRef = (0, $lX0XJ$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let inputRef = (0, $lX0XJ$useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $lX0XJ$useTimeField)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        inputRef: inputRef,
        validationBehavior: validationBehavior
    }, state, fieldRef);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $lX0XJ$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $5400c097f4765e59$export$5d8dc44abd10a920,
                state
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ],
            [
                (0, $41fb335299a4a39e$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $1f3c3b1a70cec653$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
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
const $5400c097f4765e59$export$7edc06cf1783b30f = /*#__PURE__*/ (0, $lX0XJ$forwardRef)(function DateInput(props, ref) {
    // If state is provided by DateField/TimeField, just render.
    // Otherwise (e.g. in DatePicker), we need to call hooks and create state ourselves.
    let dateFieldState = (0, $lX0XJ$useContext)($5400c097f4765e59$export$3b08bebcf796eea0);
    let timeFieldState = (0, $lX0XJ$useContext)($5400c097f4765e59$export$5d8dc44abd10a920);
    return dateFieldState || timeFieldState ? /*#__PURE__*/ (0, $lX0XJ$react).createElement($5400c097f4765e59$var$DateInputInner, {
        ...props,
        ref: ref
    }) : /*#__PURE__*/ (0, $lX0XJ$react).createElement($5400c097f4765e59$var$DateInputStandalone, {
        ...props,
        ref: ref
    });
});
const $5400c097f4765e59$var$DateInputStandalone = /*#__PURE__*/ (0, $lX0XJ$forwardRef)((props, ref)=>{
    let [dateFieldProps, fieldRef] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)({
        slot: props.slot
    }, ref, $5400c097f4765e59$export$7b3e670c86da5fe8);
    let { locale: locale } = (0, $lX0XJ$useLocale)();
    let state = (0, $lX0XJ$useDateFieldState)({
        ...dateFieldProps,
        locale: locale,
        createCalendar: $lX0XJ$createCalendar
    });
    let inputRef = (0, $lX0XJ$useRef)(null);
    let { fieldProps: fieldProps, inputProps: inputProps } = (0, $lX0XJ$useDateField)({
        ...dateFieldProps,
        inputRef: inputRef
    }, state, fieldRef);
    return /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $5400c097f4765e59$export$3b08bebcf796eea0,
                state
            ],
            [
                (0, $41fb335299a4a39e$export$37fb8590cf2c088c),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $lX0XJ$react).createElement($5400c097f4765e59$var$DateInputInner, props));
});
const $5400c097f4765e59$var$DateInputInner = /*#__PURE__*/ (0, $lX0XJ$forwardRef)((props, ref)=>{
    let { className: className, children: children } = props;
    let dateFieldState = (0, $lX0XJ$useContext)($5400c097f4765e59$export$3b08bebcf796eea0);
    let timeFieldState = (0, $lX0XJ$useContext)($5400c097f4765e59$export$5d8dc44abd10a920);
    let state = dateFieldState ?? timeFieldState;
    return /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $lX0XJ$react).Fragment, null, /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $3a442827418ebe87$export$eb2fcfdbd7ba97d4), {
        ...props,
        ref: ref,
        slot: props.slot || undefined,
        className: className ?? 'react-aria-DateInput',
        isReadOnly: state.isReadOnly,
        isInvalid: state.isInvalid,
        isDisabled: state.isDisabled
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $lX0XJ$cloneElement)(children(segment), {
            key: i
        }))), /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $41fb335299a4a39e$export$f5b8910cec6cf069), {
        className: ""
    }));
});
const $5400c097f4765e59$export$336ab7fa954c4b5f = /*#__PURE__*/ (0, $lX0XJ$forwardRef)(function DateSegment({ segment: segment, ...otherProps }, ref) {
    let dateFieldState = (0, $lX0XJ$useContext)($5400c097f4765e59$export$3b08bebcf796eea0);
    let timeFieldState = (0, $lX0XJ$useContext)($5400c097f4765e59$export$5d8dc44abd10a920);
    let state = dateFieldState ?? timeFieldState;
    let domRef = (0, $lX0XJ$useObjectRef)(ref);
    let { segmentProps: segmentProps } = (0, $lX0XJ$useDateSegment)(segment, state, domRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $lX0XJ$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $lX0XJ$useHover)({
        ...otherProps,
        isDisabled: state.isDisabled || segment.type === 'literal'
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $lX0XJ$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).span, {
        ...(0, $lX0XJ$mergeProps)((0, $lX0XJ$filterDOMProps)(otherProps, {
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


export {$5400c097f4765e59$export$7b3e670c86da5fe8 as DateFieldContext, $5400c097f4765e59$export$8e17ddc448e87c1e as TimeFieldContext, $5400c097f4765e59$export$3b08bebcf796eea0 as DateFieldStateContext, $5400c097f4765e59$export$5d8dc44abd10a920 as TimeFieldStateContext, $5400c097f4765e59$export$d9781c7894a82487 as DateField, $5400c097f4765e59$export$5eaee2322dd727eb as TimeField, $5400c097f4765e59$export$7edc06cf1783b30f as DateInput, $5400c097f4765e59$export$336ab7fa954c4b5f as DateSegment};
//# sourceMappingURL=DateField.mjs.map
