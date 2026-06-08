var $3137f87a537e3e26$exports = require("./context.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../checkbox_vars.css");
var $82ace3fdb78b7756$exports = require("../checkbox_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $1af2ca8553741739$exports = require("../form/Form.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $ahmZw$reactariauseCheckbox = require("react-aria/useCheckbox");
var $ahmZw$reactariacomponentsCheckbox = require("react-aria-components/Checkbox");
var $ahmZw$spectrumiconsuiCheckmarkSmall = require("@spectrum-icons/ui/CheckmarkSmall");
var $ahmZw$spectrumiconsuiDashSmall = require("@spectrum-icons/ui/DashSmall");
var $ahmZw$reactariaFocusRing = require("react-aria/FocusRing");
var $ahmZw$react = require("react");
var $ahmZw$reactariauseCheckboxGroup = require("react-aria/useCheckboxGroup");
var $ahmZw$reactariacomponentsslots = require("react-aria-components/slots");
var $ahmZw$reactariauseHover = require("react-aria/useHover");
var $ahmZw$reactstatelyuseToggleState = require("react-stately/useToggleState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Checkbox", function () { return $9bc060484abc63af$export$48513f6b9f8ce62d; });
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
















const $9bc060484abc63af$export$48513f6b9f8ce62d = /*#__PURE__*/ (0, $ahmZw$react.forwardRef)(function Checkbox(props, ref) {
    let originalProps = props;
    let inputRef = (0, $ahmZw$react.useRef)(null);
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref, inputRef);
    [props, domRef] = (0, $ahmZw$reactariacomponentsslots.useContextProps)(props, domRef, (0, $ahmZw$reactariacomponentsCheckbox.CheckboxContext));
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $1af2ca8553741739$exports.useFormProps)(props);
    let { isIndeterminate: isIndeterminate = false, isEmphasized: isEmphasized = false, autoFocus: autoFocus, children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    // Swap hooks depending on whether this checkbox is inside a CheckboxGroup.
    // This is a bit unorthodox. Typically, hooks cannot be called in a conditional,
    // but since the checkbox won't move in and out of a group, it should be safe.
    let groupState = (0, $ahmZw$react.useContext)((0, $3137f87a537e3e26$exports.CheckboxGroupContext));
    let { labelProps: labelProps, inputProps: inputProps, isInvalid: isInvalid, isDisabled: isDisabled } = groupState ? (0, $ahmZw$reactariauseCheckboxGroup.useCheckboxGroupItem)({
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
    }, groupState, inputRef) : (0, $ahmZw$reactariauseCheckbox.useCheckbox)(props, (0, $ahmZw$reactstatelyuseToggleState.useToggleState)(props), inputRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $ahmZw$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let markIcon = isIndeterminate ? /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement((0, ($parcel$interopDefault($ahmZw$spectrumiconsuiDashSmall))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'spectrum-Checkbox-partialCheckmark')
    }) : /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement((0, ($parcel$interopDefault($ahmZw$spectrumiconsuiCheckmarkSmall))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'spectrum-Checkbox-checkmark')
    });
    if (groupState && process.env.NODE_ENV !== 'production') {
        for (let key of [
            'isSelected',
            'defaultSelected',
            'isEmphasized'
        ])if (originalProps[key] != null) console.warn(`${key} is unsupported on individual <Checkbox> elements within a <CheckboxGroup>. Please apply these props to the group instead.`);
        if (props.value == null) console.warn('A <Checkbox> element within a <CheckboxGroup> requires a `value` property.');
    }
    return /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement("label", {
        ...labelProps,
        ...styleProps,
        ...hoverProps,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'spectrum-Checkbox', {
            'is-checked': inputProps.checked,
            'is-indeterminate': isIndeterminate,
            'spectrum-Checkbox--quiet': !isEmphasized,
            'is-invalid': isInvalid,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement((0, $ahmZw$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement("input", {
        ...inputProps,
        ref: inputRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'spectrum-Checkbox-input')
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'spectrum-Checkbox-box')
    }, markIcon), children && /*#__PURE__*/ (0, ($parcel$interopDefault($ahmZw$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($82ace3fdb78b7756$exports))), 'spectrum-Checkbox-label')
    }, children));
});


//# sourceMappingURL=Checkbox.cjs.map
