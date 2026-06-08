import {ButtonContext as $7705c033048f6da7$export$24d547caef80ccd1} from "./Button.mjs";
import {CalendarContext as $6f9a1820b787aac7$export$3b805cea1f178355, RangeCalendarContext as $6f9a1820b787aac7$export$233dd9682e1ad64b} from "./Calendar.mjs";
import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {DateFieldContext as $5400c097f4765e59$export$7b3e670c86da5fe8} from "./DateField.mjs";
import {DialogContext as $f2ff30fde7b014be$export$8b93a07348a7730c, OverlayTriggerStateContext as $f2ff30fde7b014be$export$d2f961adcb0afbe} from "./Dialog.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import {GroupContext as $3a442827418ebe87$export$f9c6924e160136d1} from "./Group.mjs";
import {HiddenDateInput as $5a15223dacad897a$export$eefa3e19139f00f3} from "./HiddenDateInput.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {PopoverContext as $542a13ca2fa5b484$export$9b9a0cd73afb7ca4} from "./Popover.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useDatePicker as $a7Jp3$useDatePicker} from "react-aria/useDatePicker";
import {useDateRangePicker as $a7Jp3$useDateRangePicker} from "react-aria/useDateRangePicker";
import {useDatePickerState as $a7Jp3$useDatePickerState} from "react-stately/useDatePickerState";
import {useDateRangePickerState as $a7Jp3$useDateRangePickerState} from "react-stately/useDateRangePickerState";
import {filterDOMProps as $a7Jp3$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $a7Jp3$mergeProps} from "react-aria/mergeProps";
import $a7Jp3$react, {createContext as $a7Jp3$createContext, forwardRef as $a7Jp3$forwardRef, useRef as $a7Jp3$useRef} from "react";
import {useFocusRing as $a7Jp3$useFocusRing} from "react-aria/useFocusRing";

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



















const $e539d00f123fdfd8$export$cf316c7f3b44c11e = /*#__PURE__*/ (0, $a7Jp3$createContext)(null);
const $e539d00f123fdfd8$export$8282edba42ee28a = /*#__PURE__*/ (0, $a7Jp3$createContext)(null);
const $e539d00f123fdfd8$export$50a10c048fdcdee9 = /*#__PURE__*/ (0, $a7Jp3$createContext)(null);
const $e539d00f123fdfd8$export$80d7ae1f804790be = /*#__PURE__*/ (0, $a7Jp3$createContext)(null);
// Contexts to clear inside the popover.
const $e539d00f123fdfd8$var$CLEAR_CONTEXTS = [
    (0, $3a442827418ebe87$export$f9c6924e160136d1),
    (0, $7705c033048f6da7$export$24d547caef80ccd1),
    (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
    (0, $efe09c6d1c304b50$export$9afb8bc826b033ea)
];
const $e539d00f123fdfd8$export$5109c6dd95d8fb00 = /*#__PURE__*/ (0, $a7Jp3$forwardRef)(function DatePicker(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $e539d00f123fdfd8$export$cf316c7f3b44c11e);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $a7Jp3$useDatePickerState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let groupRef = (0, $a7Jp3$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, fieldProps: fieldProps, buttonProps: buttonProps, dialogProps: dialogProps, calendarProps: calendarProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $a7Jp3$useDatePicker)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, groupRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $a7Jp3$useFocusRing)({
        within: true
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isInvalid,
            isOpen: state.isOpen,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DatePicker'
    });
    let DOMProps = (0, $a7Jp3$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $a7Jp3$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $e539d00f123fdfd8$export$50a10c048fdcdee9,
                state
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                {
                    ...groupProps,
                    ref: groupRef,
                    isInvalid: state.isInvalid
                }
            ],
            [
                (0, $5400c097f4765e59$export$7b3e670c86da5fe8),
                fieldProps
            ],
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    ...buttonProps,
                    isPressed: state.isOpen
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
                (0, $6f9a1820b787aac7$export$3b805cea1f178355),
                calendarProps
            ],
            [
                (0, $f2ff30fde7b014be$export$d2f961adcb0afbe),
                state
            ],
            [
                (0, $542a13ca2fa5b484$export$9b9a0cd73afb7ca4),
                {
                    trigger: 'DatePicker',
                    triggerRef: groupRef,
                    placement: 'bottom start',
                    clearContexts: $e539d00f123fdfd8$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $f2ff30fde7b014be$export$8b93a07348a7730c),
                dialogProps
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
    }, /*#__PURE__*/ (0, $a7Jp3$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $a7Jp3$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focus-within": isFocused || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-open": state.isOpen || undefined
    }), /*#__PURE__*/ (0, $a7Jp3$react).createElement((0, $5a15223dacad897a$export$eefa3e19139f00f3), {
        autoComplete: props.autoComplete,
        name: props.name,
        isDisabled: props.isDisabled,
        state: state
    }));
});
const $e539d00f123fdfd8$export$17334619f3ac2224 = /*#__PURE__*/ (0, $a7Jp3$forwardRef)(function DateRangePicker(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $e539d00f123fdfd8$export$8282edba42ee28a);
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $a7Jp3$useDateRangePickerState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let groupRef = (0, $a7Jp3$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, startFieldProps: startFieldProps, endFieldProps: endFieldProps, buttonProps: buttonProps, dialogProps: dialogProps, calendarProps: calendarProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $a7Jp3$useDateRangePicker)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, groupRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $a7Jp3$useFocusRing)({
        within: true
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.isDisabled || false,
            isInvalid: state.isInvalid,
            isOpen: state.isOpen,
            isReadOnly: props.isReadOnly || false,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DateRangePicker'
    });
    let DOMProps = (0, $a7Jp3$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $a7Jp3$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $e539d00f123fdfd8$export$80d7ae1f804790be,
                state
            ],
            [
                (0, $3a442827418ebe87$export$f9c6924e160136d1),
                {
                    ...groupProps,
                    ref: groupRef,
                    isInvalid: state.isInvalid
                }
            ],
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    ...buttonProps,
                    isPressed: state.isOpen
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
                (0, $6f9a1820b787aac7$export$233dd9682e1ad64b),
                calendarProps
            ],
            [
                (0, $f2ff30fde7b014be$export$d2f961adcb0afbe),
                state
            ],
            [
                (0, $542a13ca2fa5b484$export$9b9a0cd73afb7ca4),
                {
                    trigger: 'DateRangePicker',
                    triggerRef: groupRef,
                    placement: 'bottom start',
                    clearContexts: $e539d00f123fdfd8$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $f2ff30fde7b014be$export$8b93a07348a7730c),
                dialogProps
            ],
            [
                (0, $5400c097f4765e59$export$7b3e670c86da5fe8),
                {
                    slots: {
                        start: startFieldProps,
                        end: endFieldProps
                    }
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
    }, /*#__PURE__*/ (0, $a7Jp3$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $a7Jp3$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focus-within": isFocused || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-open": state.isOpen || undefined
    }));
});


export {$e539d00f123fdfd8$export$cf316c7f3b44c11e as DatePickerContext, $e539d00f123fdfd8$export$8282edba42ee28a as DateRangePickerContext, $e539d00f123fdfd8$export$50a10c048fdcdee9 as DatePickerStateContext, $e539d00f123fdfd8$export$80d7ae1f804790be as DateRangePickerStateContext, $e539d00f123fdfd8$export$5109c6dd95d8fb00 as DatePicker, $e539d00f123fdfd8$export$17334619f3ac2224 as DateRangePicker};
//# sourceMappingURL=DatePicker.mjs.map
