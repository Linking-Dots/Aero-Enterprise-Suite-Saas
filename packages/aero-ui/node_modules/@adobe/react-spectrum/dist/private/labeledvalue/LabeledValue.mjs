import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Field as $adcd096854d27620$export$a455218a85c89869} from "../label/Field.mjs";
import "../fieldlabel_vars.css";
import $1apCk$fieldlabel_vars_cssmjs from "../fieldlabel_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {getLocalTimeZone as $1apCk$getLocalTimeZone, today as $1apCk$today, toCalendarDateTime as $1apCk$toCalendarDateTime} from "@internationalized/date";
import {filterDOMProps as $1apCk$filterDOMProps} from "react-aria/filterDOMProps";
import $1apCk$react, {useEffect as $1apCk$useEffect} from "react";
import {useDateFormatter as $1apCk$useDateFormatter} from "react-aria/useDateFormatter";
import {useListFormatter as $1apCk$useListFormatter} from "react-aria/useListFormatter";
import {useNumberFormatter as $1apCk$useNumberFormatter} from "react-aria/useNumberFormatter";


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









const $57c5392907f14540$export$d1328f67a56fa517 = /*#__PURE__*/ (0, $1apCk$react).forwardRef(function LabeledValue(props, ref) {
    let { value: value, formatOptions: formatOptions } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    (0, $1apCk$useEffect)(()=>{
        if (domRef?.current && domRef.current.querySelectorAll('input, [contenteditable], textarea').length > 0) throw new Error('LabeledValue cannot contain an editable value.');
    }, [
        domRef
    ]);
    let children;
    if (Array.isArray(value)) children = /*#__PURE__*/ (0, $1apCk$react).createElement($57c5392907f14540$var$FormattedStringList, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && 'start' in value && typeof value.start === 'number' && typeof value.end === 'number') children = /*#__PURE__*/ (0, $1apCk$react).createElement($57c5392907f14540$var$FormattedNumber, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && 'start' in value && typeof value.start !== 'number' && typeof value.end !== 'number') children = /*#__PURE__*/ (0, $1apCk$react).createElement($57c5392907f14540$var$FormattedDate, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'number') children = /*#__PURE__*/ (0, $1apCk$react).createElement($57c5392907f14540$var$FormattedNumber, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && ('calendar' in value || 'hour' in value) || value instanceof Date) children = /*#__PURE__*/ (0, $1apCk$react).createElement($57c5392907f14540$var$FormattedDate, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'string') children = value;
    if (/*#__PURE__*/ (0, $1apCk$react).isValidElement(value)) children = value;
    return /*#__PURE__*/ (0, $1apCk$react).createElement((0, $adcd096854d27620$export$a455218a85c89869), {
        ...props,
        wrapperProps: (0, $1apCk$filterDOMProps)(props),
        ref: domRef,
        elementType: "span",
        wrapperClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1apCk$fieldlabel_vars_cssmjs))), 'spectrum-LabeledValue')
    }, /*#__PURE__*/ (0, $1apCk$react).createElement("span", null, children));
});
function $57c5392907f14540$var$FormattedStringList(props) {
    let stringFormatter = (0, $1apCk$useListFormatter)(props.formatOptions);
    return /*#__PURE__*/ (0, $1apCk$react).createElement((0, $1apCk$react).Fragment, null, stringFormatter.format(props.value));
}
function $57c5392907f14540$var$FormattedNumber(props) {
    let numberFormatter = (0, $1apCk$useNumberFormatter)(props.formatOptions);
    let value = props.value;
    if (typeof value === 'object') return /*#__PURE__*/ (0, $1apCk$react).createElement((0, $1apCk$react).Fragment, null, numberFormatter.formatRange(value.start, value.end));
    return /*#__PURE__*/ (0, $1apCk$react).createElement((0, $1apCk$react).Fragment, null, numberFormatter.format(value));
}
function $57c5392907f14540$var$FormattedDate(props) {
    let { value: value, formatOptions: formatOptions } = props;
    if (!formatOptions) formatOptions = $57c5392907f14540$var$getDefaultFormatOptions('start' in value ? value.start : value);
    let dateFormatter = (0, $1apCk$useDateFormatter)(formatOptions);
    let timeZone = dateFormatter.resolvedOptions().timeZone || (0, $1apCk$getLocalTimeZone)();
    let final;
    if ('start' in value && 'end' in value) {
        let start = value.start;
        let end = value.end;
        start = $57c5392907f14540$var$convertDateTime(start, timeZone);
        end = $57c5392907f14540$var$convertDateTime(end, timeZone);
        return /*#__PURE__*/ (0, $1apCk$react).createElement((0, $1apCk$react).Fragment, null, dateFormatter.formatRange(start, end));
    }
    final = $57c5392907f14540$var$convertDateTime(value, timeZone);
    return /*#__PURE__*/ (0, $1apCk$react).createElement((0, $1apCk$react).Fragment, null, dateFormatter.format(final));
}
function $57c5392907f14540$var$convertDateTime(value, timeZone) {
    if ('timeZone' in value) return value.toDate();
    else if ('calendar' in value) return value.toDate(timeZone);
    else if (!(value instanceof Date)) return $57c5392907f14540$var$convertValue(value).toDate(timeZone);
    return value;
}
function $57c5392907f14540$var$getDefaultFormatOptions(value) {
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
function $57c5392907f14540$var$convertValue(value) {
    let date = (0, $1apCk$today)((0, $1apCk$getLocalTimeZone)());
    return (0, $1apCk$toCalendarDateTime)(date, value);
}


export {$57c5392907f14540$export$d1328f67a56fa517 as LabeledValue};
//# sourceMappingURL=LabeledValue.mjs.map
