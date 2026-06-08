var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $f3068c15cd7dcac2$exports = require("./Group.cjs");
var $42971d650511669b$exports = require("./HiddenDateInput.cjs");
var $81dc1c05bf045ce0$exports = require("./Input.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $5V5U3$reactariauseDateField = require("react-aria/useDateField");
var $5V5U3$reactariauseTimeField = require("react-aria/useTimeField");
var $5V5U3$internationalizeddate = require("@internationalized/date");
var $5V5U3$reactstatelyuseDateFieldState = require("react-stately/useDateFieldState");
var $5V5U3$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $5V5U3$reactariamergeProps = require("react-aria/mergeProps");
var $5V5U3$react = require("react");
var $5V5U3$reactstatelyuseTimeFieldState = require("react-stately/useTimeFieldState");
var $5V5U3$reactariauseFocusRing = require("react-aria/useFocusRing");
var $5V5U3$reactariauseHover = require("react-aria/useHover");
var $5V5U3$reactariaI18nProvider = require("react-aria/I18nProvider");
var $5V5U3$reactariauseObjectRef = require("react-aria/useObjectRef");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DateFieldContext", function () { return $45bc69f809bc5ce9$export$7b3e670c86da5fe8; });
$parcel$export(module.exports, "TimeFieldContext", function () { return $45bc69f809bc5ce9$export$8e17ddc448e87c1e; });
$parcel$export(module.exports, "DateFieldStateContext", function () { return $45bc69f809bc5ce9$export$3b08bebcf796eea0; });
$parcel$export(module.exports, "TimeFieldStateContext", function () { return $45bc69f809bc5ce9$export$5d8dc44abd10a920; });
$parcel$export(module.exports, "DateField", function () { return $45bc69f809bc5ce9$export$d9781c7894a82487; });
$parcel$export(module.exports, "TimeField", function () { return $45bc69f809bc5ce9$export$5eaee2322dd727eb; });
$parcel$export(module.exports, "DateInput", function () { return $45bc69f809bc5ce9$export$7edc06cf1783b30f; });
$parcel$export(module.exports, "DateSegment", function () { return $45bc69f809bc5ce9$export$336ab7fa954c4b5f; });
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



















const $45bc69f809bc5ce9$export$7b3e670c86da5fe8 = /*#__PURE__*/ (0, $5V5U3$react.createContext)(null);
const $45bc69f809bc5ce9$export$8e17ddc448e87c1e = /*#__PURE__*/ (0, $5V5U3$react.createContext)(null);
const $45bc69f809bc5ce9$export$3b08bebcf796eea0 = /*#__PURE__*/ (0, $5V5U3$react.createContext)(null);
const $45bc69f809bc5ce9$export$5d8dc44abd10a920 = /*#__PURE__*/ (0, $5V5U3$react.createContext)(null);
const $45bc69f809bc5ce9$export$d9781c7894a82487 = /*#__PURE__*/ (0, $5V5U3$react.forwardRef)(function DateField(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $45bc69f809bc5ce9$export$7b3e670c86da5fe8);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let { locale: locale } = (0, $5V5U3$reactariaI18nProvider.useLocale)();
    let state = (0, $5V5U3$reactstatelyuseDateFieldState.useDateFieldState)({
        ...props,
        locale: locale,
        createCalendar: $5V5U3$internationalizeddate.createCalendar,
        validationBehavior: validationBehavior
    });
    let fieldRef = (0, $5V5U3$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let inputRef = (0, $5V5U3$react.useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $5V5U3$reactariauseDateField.useDateField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        inputRef: inputRef,
        validationBehavior: validationBehavior
    }, state, fieldRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        values: {
            state: state,
            isInvalid: state.isInvalid,
            isDisabled: state.isDisabled,
            isReadOnly: state.isReadOnly,
            isRequired: props.isRequired || false
        },
        defaultClassName: 'react-aria-DateField'
    });
    let DOMProps = (0, $5V5U3$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $45bc69f809bc5ce9$export$3b08bebcf796eea0,
                state
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ],
            [
                (0, $81dc1c05bf045ce0$exports.InputContext),
                {
                    ...inputProps,
                    ref: inputRef
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-invalid": state.isInvalid || undefined,
        "data-disabled": state.isDisabled || undefined,
        "data-readonly": state.isReadOnly || undefined,
        "data-required": props.isRequired || undefined
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $42971d650511669b$exports.HiddenDateInput), {
        autoComplete: props.autoComplete,
        name: props.name,
        isDisabled: props.isDisabled,
        state: state
    }));
});
const $45bc69f809bc5ce9$export$5eaee2322dd727eb = /*#__PURE__*/ (0, $5V5U3$react.forwardRef)(function TimeField(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $45bc69f809bc5ce9$export$8e17ddc448e87c1e);
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let { locale: locale } = (0, $5V5U3$reactariaI18nProvider.useLocale)();
    let state = (0, $5V5U3$reactstatelyuseTimeFieldState.useTimeFieldState)({
        ...props,
        locale: locale,
        validationBehavior: validationBehavior
    });
    let fieldRef = (0, $5V5U3$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let inputRef = (0, $5V5U3$react.useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, ...validation } = (0, $5V5U3$reactariauseTimeField.useTimeField)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        inputRef: inputRef,
        validationBehavior: validationBehavior
    }, state, fieldRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $5V5U3$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $45bc69f809bc5ce9$export$5d8dc44abd10a920,
                state
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ],
            [
                (0, $81dc1c05bf045ce0$exports.InputContext),
                {
                    ...inputProps,
                    ref: inputRef
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
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
const $45bc69f809bc5ce9$export$7edc06cf1783b30f = /*#__PURE__*/ (0, $5V5U3$react.forwardRef)(function DateInput(props, ref) {
    // If state is provided by DateField/TimeField, just render.
    // Otherwise (e.g. in DatePicker), we need to call hooks and create state ourselves.
    let dateFieldState = (0, $5V5U3$react.useContext)($45bc69f809bc5ce9$export$3b08bebcf796eea0);
    let timeFieldState = (0, $5V5U3$react.useContext)($45bc69f809bc5ce9$export$5d8dc44abd10a920);
    return dateFieldState || timeFieldState ? /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement($45bc69f809bc5ce9$var$DateInputInner, {
        ...props,
        ref: ref
    }) : /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement($45bc69f809bc5ce9$var$DateInputStandalone, {
        ...props,
        ref: ref
    });
});
const $45bc69f809bc5ce9$var$DateInputStandalone = /*#__PURE__*/ (0, $5V5U3$react.forwardRef)((props, ref)=>{
    let [dateFieldProps, fieldRef] = (0, $048d76b84370f141$exports.useContextProps)({
        slot: props.slot
    }, ref, $45bc69f809bc5ce9$export$7b3e670c86da5fe8);
    let { locale: locale } = (0, $5V5U3$reactariaI18nProvider.useLocale)();
    let state = (0, $5V5U3$reactstatelyuseDateFieldState.useDateFieldState)({
        ...dateFieldProps,
        locale: locale,
        createCalendar: $5V5U3$internationalizeddate.createCalendar
    });
    let inputRef = (0, $5V5U3$react.useRef)(null);
    let { fieldProps: fieldProps, inputProps: inputProps } = (0, $5V5U3$reactariauseDateField.useDateField)({
        ...dateFieldProps,
        inputRef: inputRef
    }, state, fieldRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $45bc69f809bc5ce9$export$3b08bebcf796eea0,
                state
            ],
            [
                (0, $81dc1c05bf045ce0$exports.InputContext),
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                (0, $f3068c15cd7dcac2$exports.GroupContext),
                {
                    ...fieldProps,
                    ref: fieldRef,
                    isInvalid: state.isInvalid,
                    isDisabled: state.isDisabled
                }
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement($45bc69f809bc5ce9$var$DateInputInner, props));
});
const $45bc69f809bc5ce9$var$DateInputInner = /*#__PURE__*/ (0, $5V5U3$react.forwardRef)((props, ref)=>{
    let { className: className, children: children } = props;
    let dateFieldState = (0, $5V5U3$react.useContext)($45bc69f809bc5ce9$export$3b08bebcf796eea0);
    let timeFieldState = (0, $5V5U3$react.useContext)($45bc69f809bc5ce9$export$5d8dc44abd10a920);
    let state = dateFieldState ?? timeFieldState;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, ($parcel$interopDefault($5V5U3$react))).Fragment, null, /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $f3068c15cd7dcac2$exports.Group), {
        ...props,
        ref: ref,
        slot: props.slot || undefined,
        className: className ?? 'react-aria-DateInput',
        isReadOnly: state.isReadOnly,
        isInvalid: state.isInvalid,
        isDisabled: state.isDisabled
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, $5V5U3$react.cloneElement)(children(segment), {
            key: i
        }))), /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $81dc1c05bf045ce0$exports.Input), {
        className: ""
    }));
});
const $45bc69f809bc5ce9$export$336ab7fa954c4b5f = /*#__PURE__*/ (0, $5V5U3$react.forwardRef)(function DateSegment({ segment: segment, ...otherProps }, ref) {
    let dateFieldState = (0, $5V5U3$react.useContext)($45bc69f809bc5ce9$export$3b08bebcf796eea0);
    let timeFieldState = (0, $5V5U3$react.useContext)($45bc69f809bc5ce9$export$5d8dc44abd10a920);
    let state = dateFieldState ?? timeFieldState;
    let domRef = (0, $5V5U3$reactariauseObjectRef.useObjectRef)(ref);
    let { segmentProps: segmentProps } = (0, $5V5U3$reactariauseDateField.useDateSegment)(segment, state, domRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $5V5U3$reactariauseFocusRing.useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $5V5U3$reactariauseHover.useHover)({
        ...otherProps,
        isDisabled: state.isDisabled || segment.type === 'literal'
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5V5U3$react))).createElement((0, $048d76b84370f141$exports.dom).span, {
        ...(0, $5V5U3$reactariamergeProps.mergeProps)((0, $5V5U3$reactariafilterDOMProps.filterDOMProps)(otherProps, {
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


//# sourceMappingURL=DateField.cjs.map
