var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b93966d678e0af07$exports = require("../label/Field.cjs");
require("../fieldlabel_vars.css");
var $53185441bef09fa8$exports = require("../fieldlabel_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $1IxKs$internationalizeddate = require("@internationalized/date");
var $1IxKs$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $1IxKs$react = require("react");
var $1IxKs$reactariauseDateFormatter = require("react-aria/useDateFormatter");
var $1IxKs$reactariauseListFormatter = require("react-aria/useListFormatter");
var $1IxKs$reactariauseNumberFormatter = require("react-aria/useNumberFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "LabeledValue", function () { return $3c3ead20e4035c70$export$d1328f67a56fa517; });
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









const $3c3ead20e4035c70$export$d1328f67a56fa517 = /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).forwardRef(function LabeledValue(props, ref) {
    let { value: value, formatOptions: formatOptions } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    (0, $1IxKs$react.useEffect)(()=>{
        if (domRef?.current && domRef.current.querySelectorAll('input, [contenteditable], textarea').length > 0) throw new Error('LabeledValue cannot contain an editable value.');
    }, [
        domRef
    ]);
    let children;
    if (Array.isArray(value)) children = /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement($3c3ead20e4035c70$var$FormattedStringList, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && 'start' in value && typeof value.start === 'number' && typeof value.end === 'number') children = /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement($3c3ead20e4035c70$var$FormattedNumber, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && 'start' in value && typeof value.start !== 'number' && typeof value.end !== 'number') children = /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement($3c3ead20e4035c70$var$FormattedDate, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'number') children = /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement($3c3ead20e4035c70$var$FormattedNumber, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && ('calendar' in value || 'hour' in value) || value instanceof Date) children = /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement($3c3ead20e4035c70$var$FormattedDate, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'string') children = value;
    if (/*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).isValidElement(value)) children = value;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement((0, $b93966d678e0af07$exports.Field), {
        ...props,
        wrapperProps: (0, $1IxKs$reactariafilterDOMProps.filterDOMProps)(props),
        ref: domRef,
        elementType: "span",
        wrapperClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($53185441bef09fa8$exports))), 'spectrum-LabeledValue')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement("span", null, children));
});
function $3c3ead20e4035c70$var$FormattedStringList(props) {
    let stringFormatter = (0, $1IxKs$reactariauseListFormatter.useListFormatter)(props.formatOptions);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement((0, ($parcel$interopDefault($1IxKs$react))).Fragment, null, stringFormatter.format(props.value));
}
function $3c3ead20e4035c70$var$FormattedNumber(props) {
    let numberFormatter = (0, $1IxKs$reactariauseNumberFormatter.useNumberFormatter)(props.formatOptions);
    let value = props.value;
    if (typeof value === 'object') return /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement((0, ($parcel$interopDefault($1IxKs$react))).Fragment, null, numberFormatter.formatRange(value.start, value.end));
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement((0, ($parcel$interopDefault($1IxKs$react))).Fragment, null, numberFormatter.format(value));
}
function $3c3ead20e4035c70$var$FormattedDate(props) {
    let { value: value, formatOptions: formatOptions } = props;
    if (!formatOptions) formatOptions = $3c3ead20e4035c70$var$getDefaultFormatOptions('start' in value ? value.start : value);
    let dateFormatter = (0, $1IxKs$reactariauseDateFormatter.useDateFormatter)(formatOptions);
    let timeZone = dateFormatter.resolvedOptions().timeZone || (0, $1IxKs$internationalizeddate.getLocalTimeZone)();
    let final;
    if ('start' in value && 'end' in value) {
        let start = value.start;
        let end = value.end;
        start = $3c3ead20e4035c70$var$convertDateTime(start, timeZone);
        end = $3c3ead20e4035c70$var$convertDateTime(end, timeZone);
        return /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement((0, ($parcel$interopDefault($1IxKs$react))).Fragment, null, dateFormatter.formatRange(start, end));
    }
    final = $3c3ead20e4035c70$var$convertDateTime(value, timeZone);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1IxKs$react))).createElement((0, ($parcel$interopDefault($1IxKs$react))).Fragment, null, dateFormatter.format(final));
}
function $3c3ead20e4035c70$var$convertDateTime(value, timeZone) {
    if ('timeZone' in value) return value.toDate();
    else if ('calendar' in value) return value.toDate(timeZone);
    else if (!(value instanceof Date)) return $3c3ead20e4035c70$var$convertValue(value).toDate(timeZone);
    return value;
}
function $3c3ead20e4035c70$var$getDefaultFormatOptions(value) {
    if (value instanceof Date) return {
        dateStyle: 'long',
        timeStyle: 'short'
    };
    else if ('timeZone' in value) return {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        timeZone: value.timeZone,
        timeZoneName: 'short'
    };
    else if ('hour' in value && 'year' in value) return {
        dateStyle: 'long',
        timeStyle: 'short'
    };
    else if ('hour' in value) return {
        timeStyle: 'short'
    };
    else return {
        dateStyle: 'long'
    };
}
function $3c3ead20e4035c70$var$convertValue(value) {
    let date = (0, $1IxKs$internationalizeddate.today)((0, $1IxKs$internationalizeddate.getLocalTimeZone)());
    return (0, $1IxKs$internationalizeddate.toCalendarDateTime)(date, value);
}


//# sourceMappingURL=LabeledValue.cjs.map
