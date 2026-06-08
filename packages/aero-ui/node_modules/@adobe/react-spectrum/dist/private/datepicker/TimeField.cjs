var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $9b919f07381d65d4$exports = require("./DatePickerSegment.cjs");
require("./styles.css");
var $25dd6e69bdd309d3$exports = require("./styles_css.cjs");
var $b93966d678e0af07$exports = require("../label/Field.cjs");
var $5d83a0dbed853d9d$exports = require("./Input.cjs");
var $7f5eff3a70a58c6f$exports = require("./utils.cjs");
var $1af2ca8553741739$exports = require("../form/Form.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $9iPXk$reactariauseTimeField = require("react-aria/useTimeField");
var $9iPXk$react = require("react");
var $9iPXk$reactariaI18nProvider = require("react-aria/I18nProvider");
var $9iPXk$reactstatelyuseTimeFieldState = require("react-stately/useTimeFieldState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TimeField", function () { return $54e7dc5129906ae8$export$5eaee2322dd727eb; });
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











const $54e7dc5129906ae8$export$5eaee2322dd727eb = /*#__PURE__*/ (0, ($parcel$interopDefault($9iPXk$react))).forwardRef(function TimeField(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $1af2ca8553741739$exports.useFormProps)(props);
    let { autoFocus: autoFocus, isDisabled: isDisabled, isReadOnly: isReadOnly, isRequired: isRequired, isQuiet: isQuiet } = props;
    let domRef = (0, $7f5eff3a70a58c6f$exports.useFocusManagerRef)(ref);
    let { locale: locale } = (0, $9iPXk$reactariaI18nProvider.useLocale)();
    let state = (0, $9iPXk$reactstatelyuseTimeFieldState.useTimeFieldState)({
        ...props,
        locale: locale
    });
    let fieldRef = (0, $9iPXk$react.useRef)(null);
    let inputRef = (0, $9iPXk$react.useRef)(null);
    let { labelProps: labelProps, fieldProps: fieldProps, inputProps: inputProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $9iPXk$reactariauseTimeField.useTimeField)({
        ...props,
        inputRef: inputRef
    }, state, fieldRef);
    let validationState = state.validationState || (isInvalid ? 'invalid' : null);
    let approximateWidth = (0, $7f5eff3a70a58c6f$exports.useFormattedDateWidth)(state) + 'ch';
    return /*#__PURE__*/ (0, ($parcel$interopDefault($9iPXk$react))).createElement((0, $b93966d678e0af07$exports.Field), {
        ...props,
        ref: domRef,
        elementType: "span",
        labelProps: labelProps,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        validationState: validationState ?? undefined,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        wrapperClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-TimeField-fieldWrapper')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9iPXk$react))).createElement((0, $5d83a0dbed853d9d$exports.Input), {
        ref: fieldRef,
        fieldProps: fieldProps,
        isDisabled: isDisabled,
        isQuiet: isQuiet,
        autoFocus: autoFocus,
        validationState: validationState,
        minWidth: approximateWidth,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-TimeField')
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, ($parcel$interopDefault($9iPXk$react))).createElement((0, $9b919f07381d65d4$exports.DatePickerSegment), {
            key: i,
            segment: segment,
            state: state,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isRequired: isRequired
        })), /*#__PURE__*/ (0, ($parcel$interopDefault($9iPXk$react))).createElement("input", {
        ...inputProps,
        ref: inputRef
    })));
});


//# sourceMappingURL=TimeField.cjs.map
