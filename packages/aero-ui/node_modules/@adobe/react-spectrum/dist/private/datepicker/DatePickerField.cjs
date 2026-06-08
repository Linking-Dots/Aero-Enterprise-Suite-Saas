var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $9b919f07381d65d4$exports = require("./DatePickerSegment.cjs");
require("./styles.css");
var $25dd6e69bdd309d3$exports = require("./styles_css.cjs");
var $iC36Z$internationalizeddate = require("@internationalized/date");
var $iC36Z$reactariauseDateField = require("react-aria/useDateField");
var $iC36Z$react = require("react");
var $iC36Z$reactstatelyuseDateFieldState = require("react-stately/useDateFieldState");
var $iC36Z$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DatePickerField", function () { return $6a1721f36ff2e171$export$34dc4cfa15ead1; });
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







function $6a1721f36ff2e171$export$34dc4cfa15ead1(props) {
    let { isDisabled: isDisabled, isReadOnly: isReadOnly, isRequired: isRequired, inputClassName: inputClassName } = props;
    let ref = (0, $iC36Z$react.useRef)(null);
    let { locale: locale } = (0, $iC36Z$reactariaI18nProvider.useLocale)();
    let state = (0, $iC36Z$reactstatelyuseDateFieldState.useDateFieldState)({
        ...props,
        locale: locale,
        createCalendar: $iC36Z$internationalizeddate.createCalendar
    });
    let inputRef = (0, $iC36Z$react.useRef)(null);
    let { fieldProps: fieldProps, inputProps: inputProps } = (0, $iC36Z$reactariauseDateField.useDateField)({
        ...props,
        inputRef: inputRef
    }, state, ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($iC36Z$react))).createElement("span", {
        ...fieldProps,
        "data-testid": props['data-testid'],
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-Datepicker-segments', inputClassName),
        ref: ref
    }, state.segments.map((segment, i)=>/*#__PURE__*/ (0, ($parcel$interopDefault($iC36Z$react))).createElement((0, $9b919f07381d65d4$exports.DatePickerSegment), {
            key: i,
            segment: segment,
            state: state,
            isDisabled: isDisabled,
            isReadOnly: isReadOnly,
            isRequired: isRequired
        })), /*#__PURE__*/ (0, ($parcel$interopDefault($iC36Z$react))).createElement("input", {
        ...inputProps,
        ref: inputRef
    }));
}


//# sourceMappingURL=DatePickerField.cjs.map
