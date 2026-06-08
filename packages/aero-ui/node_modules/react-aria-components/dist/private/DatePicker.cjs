var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $27a812393b1f8a86$exports = require("./Calendar.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $45bc69f809bc5ce9$exports = require("./DateField.cjs");
var $88595bf043e542ec$exports = require("./Dialog.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $f3068c15cd7dcac2$exports = require("./Group.cjs");
var $42971d650511669b$exports = require("./HiddenDateInput.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $74e35a768d38d46b$exports = require("./Popover.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $7wQ3T$reactariauseDatePicker = require("react-aria/useDatePicker");
var $7wQ3T$reactariauseDateRangePicker = require("react-aria/useDateRangePicker");
var $7wQ3T$reactstatelyuseDatePickerState = require("react-stately/useDatePickerState");
var $7wQ3T$reactstatelyuseDateRangePickerState = require("react-stately/useDateRangePickerState");
var $7wQ3T$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $7wQ3T$reactariamergeProps = require("react-aria/mergeProps");
var $7wQ3T$react = require("react");
var $7wQ3T$reactariauseFocusRing = require("react-aria/useFocusRing");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DatePickerContext", function () { return $e06e9b1044c23255$export$cf316c7f3b44c11e; });
$parcel$export(module.exports, "DateRangePickerContext", function () { return $e06e9b1044c23255$export$8282edba42ee28a; });
$parcel$export(module.exports, "DatePickerStateContext", function () { return $e06e9b1044c23255$export$50a10c048fdcdee9; });
$parcel$export(module.exports, "DateRangePickerStateContext", function () { return $e06e9b1044c23255$export$80d7ae1f804790be; });
$parcel$export(module.exports, "DatePicker", function () { return $e06e9b1044c23255$export$5109c6dd95d8fb00; });
$parcel$export(module.exports, "DateRangePicker", function () { return $e06e9b1044c23255$export$17334619f3ac2224; });
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



















const $e06e9b1044c23255$export$cf316c7f3b44c11e = /*#__PURE__*/ (0, $7wQ3T$react.createContext)(null);
const $e06e9b1044c23255$export$8282edba42ee28a = /*#__PURE__*/ (0, $7wQ3T$react.createContext)(null);
const $e06e9b1044c23255$export$50a10c048fdcdee9 = /*#__PURE__*/ (0, $7wQ3T$react.createContext)(null);
const $e06e9b1044c23255$export$80d7ae1f804790be = /*#__PURE__*/ (0, $7wQ3T$react.createContext)(null);
// Contexts to clear inside the popover.
const $e06e9b1044c23255$var$CLEAR_CONTEXTS = [
    (0, $f3068c15cd7dcac2$exports.GroupContext),
    (0, $16c7f9b22cce3838$exports.ButtonContext),
    (0, $d5d46822336ca1e1$exports.LabelContext),
    (0, $cab7d9a238d19c33$exports.TextContext)
];
const $e06e9b1044c23255$export$5109c6dd95d8fb00 = /*#__PURE__*/ (0, $7wQ3T$react.forwardRef)(function DatePicker(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $e06e9b1044c23255$export$cf316c7f3b44c11e);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $7wQ3T$reactstatelyuseDatePickerState.useDatePickerState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let groupRef = (0, $7wQ3T$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, fieldProps: fieldProps, buttonProps: buttonProps, dialogProps: dialogProps, calendarProps: calendarProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $7wQ3T$reactariauseDatePicker.useDatePicker)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, groupRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $7wQ3T$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $7wQ3T$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($7wQ3T$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $e06e9b1044c23255$export$50a10c048fdcdee9,
                state
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    ...groupProps,
                    ref: groupRef,
                    isInvalid: state.isInvalid
                }
            ],
            [
                (0, $45bc69f809bc5ce9$exports.DateFieldContext),
                fieldProps
            ],
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    ...buttonProps,
                    isPressed: state.isOpen
                }
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $27a812393b1f8a86$exports.CalendarContext),
                calendarProps
            ],
            [
                (0, $88595bf043e542ec$exports.OverlayTriggerStateContext),
                state
            ],
            [
                (0, $74e35a768d38d46b$exports.PopoverContext),
                {
                    trigger: 'DatePicker',
                    triggerRef: groupRef,
                    placement: 'bottom start',
                    clearContexts: $e06e9b1044c23255$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $88595bf043e542ec$exports.DialogContext),
                dialogProps
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($7wQ3T$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $7wQ3T$reactariamergeProps.mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focus-within": isFocused || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-readonly": props.isReadOnly || undefined,
        "data-required": props.isRequired || undefined,
        "data-open": state.isOpen || undefined
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($7wQ3T$react))).createElement((0, $42971d650511669b$exports.HiddenDateInput), {
        autoComplete: props.autoComplete,
        name: props.name,
        isDisabled: props.isDisabled,
        state: state
    }));
});
const $e06e9b1044c23255$export$17334619f3ac2224 = /*#__PURE__*/ (0, $7wQ3T$react.forwardRef)(function DateRangePicker(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $e06e9b1044c23255$export$8282edba42ee28a);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $7wQ3T$reactstatelyuseDateRangePickerState.useDateRangePickerState)({
        ...props,
        validationBehavior: validationBehavior
    });
    let groupRef = (0, $7wQ3T$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { groupProps: groupProps, labelProps: labelProps, startFieldProps: startFieldProps, endFieldProps: endFieldProps, buttonProps: buttonProps, dialogProps: dialogProps, calendarProps: calendarProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $7wQ3T$reactariauseDateRangePicker.useDateRangePicker)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, groupRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $7wQ3T$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $7wQ3T$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($7wQ3T$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $e06e9b1044c23255$export$80d7ae1f804790be,
                state
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    ...groupProps,
                    ref: groupRef,
                    isInvalid: state.isInvalid
                }
            ],
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    ...buttonProps,
                    isPressed: state.isOpen
                }
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $27a812393b1f8a86$exports.RangeCalendarContext),
                calendarProps
            ],
            [
                (0, $88595bf043e542ec$exports.OverlayTriggerStateContext),
                state
            ],
            [
                (0, $74e35a768d38d46b$exports.PopoverContext),
                {
                    trigger: 'DateRangePicker',
                    triggerRef: groupRef,
                    placement: 'bottom start',
                    clearContexts: $e06e9b1044c23255$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $88595bf043e542ec$exports.DialogContext),
                dialogProps
            ],
            [
                (0, $45bc69f809bc5ce9$exports.DateFieldContext),
                {
                    slots: {
                        start: startFieldProps,
                        end: endFieldProps
                    }
                }
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($7wQ3T$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $7wQ3T$reactariamergeProps.mergeProps)(DOMProps, renderProps, focusProps),
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


//# sourceMappingURL=DatePicker.cjs.map
