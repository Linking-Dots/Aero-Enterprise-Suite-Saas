import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Field as $3967792f95357356$export$a455218a85c89869} from "../label/Field.js";
import "../fieldlabel_vars.css";
import $jN8cE$fieldlabel_vars_cssmjs from "../fieldlabel_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {getLocalTimeZone as $jN8cE$getLocalTimeZone, today as $jN8cE$today, toCalendarDateTime as $jN8cE$toCalendarDateTime} from "@internationalized/date";
import {filterDOMProps as $jN8cE$filterDOMProps} from "react-aria/filterDOMProps";
import $jN8cE$react, {useEffect as $jN8cE$useEffect} from "react";
import {useDateFormatter as $jN8cE$useDateFormatter} from "react-aria/useDateFormatter";
import {useListFormatter as $jN8cE$useListFormatter} from "react-aria/useListFormatter";
import {useNumberFormatter as $jN8cE$useNumberFormatter} from "react-aria/useNumberFormatter";


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









const $4f61dbf9ff82452c$export$d1328f67a56fa517 = /*#__PURE__*/ (0, $jN8cE$react).forwardRef(function LabeledValue(props, ref) {
    let { value: value, formatOptions: formatOptions } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    (0, $jN8cE$useEffect)(()=>{
        if ((domRef === null || domRef === void 0 ? void 0 : domRef.current) && domRef.current.querySelectorAll('input, [contenteditable], textarea').length > 0) throw new Error('LabeledValue cannot contain an editable value.');
    }, [
        domRef
    ]);
    let children;
    if (Array.isArray(value)) children = /*#__PURE__*/ (0, $jN8cE$react).createElement($4f61dbf9ff82452c$var$FormattedStringList, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && 'start' in value && typeof value.start === 'number' && typeof value.end === 'number') children = /*#__PURE__*/ (0, $jN8cE$react).createElement($4f61dbf9ff82452c$var$FormattedNumber, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && 'start' in value && typeof value.start !== 'number' && typeof value.end !== 'number') children = /*#__PURE__*/ (0, $jN8cE$react).createElement($4f61dbf9ff82452c$var$FormattedDate, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'number') children = /*#__PURE__*/ (0, $jN8cE$react).createElement($4f61dbf9ff82452c$var$FormattedNumber, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'object' && ('calendar' in value || 'hour' in value) || value instanceof Date) children = /*#__PURE__*/ (0, $jN8cE$react).createElement($4f61dbf9ff82452c$var$FormattedDate, {
        value: value,
        formatOptions: formatOptions
    });
    if (typeof value === 'string') children = value;
    if (/*#__PURE__*/ (0, $jN8cE$react).isValidElement(value)) children = value;
    return /*#__PURE__*/ (0, $jN8cE$react).createElement((0, $3967792f95357356$export$a455218a85c89869), {
        ...props,
        wrapperProps: (0, $jN8cE$filterDOMProps)(props),
        ref: domRef,
        elementType: "span",
        wrapperClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jN8cE$fieldlabel_vars_cssmjs))), 'spectrum-LabeledValue')
    }, /*#__PURE__*/ (0, $jN8cE$react).createElement("span", null, children));
});
function $4f61dbf9ff82452c$var$FormattedStringList(props) {
    let stringFormatter = (0, $jN8cE$useListFormatter)(props.formatOptions);
    return /*#__PURE__*/ (0, $jN8cE$react).createElement((0, $jN8cE$react).Fragment, null, stringFormatter.format(props.value));
}
function $4f61dbf9ff82452c$var$FormattedNumber(props) {
    let numberFormatter = (0, $jN8cE$useNumberFormatter)(props.formatOptions);
    let value = props.value;
    if (typeof value === 'object') return /*#__PURE__*/ (0, $jN8cE$react).createElement((0, $jN8cE$react).Fragment, null, numberFormatter.formatRange(value.start, value.end));
    return /*#__PURE__*/ (0, $jN8cE$react).createElement((0, $jN8cE$react).Fragment, null, numberFormatter.format(value));
}
function $4f61dbf9ff82452c$var$FormattedDate(props) {
    let { value: value, formatOptions: formatOptions } = props;
    if (!formatOptions) formatOptions = $4f61dbf9ff82452c$var$getDefaultFormatOptions('start' in value ? value.start : value);
    let dateFormatter = (0, $jN8cE$useDateFormatter)(formatOptions);
    let timeZone = dateFormatter.resolvedOptions().timeZone || (0, $jN8cE$getLocalTimeZone)();
    let final;
    if ('start' in value && 'end' in value) {
        let start = value.start;
        let end = value.end;
        start = $4f61dbf9ff82452c$var$convertDateTime(start, timeZone);
        end = $4f61dbf9ff82452c$var$convertDateTime(end, timeZone);
        return /*#__PURE__*/ (0, $jN8cE$react).createElement((0, $jN8cE$react).Fragment, null, dateFormatter.formatRange(start, end));
    }
    final = $4f61dbf9ff82452c$var$convertDateTime(value, timeZone);
    return /*#__PURE__*/ (0, $jN8cE$react).createElement((0, $jN8cE$react).Fragment, null, dateFormatter.format(final));
}
function $4f61dbf9ff82452c$var$convertDateTime(value, timeZone) {
    if ('timeZone' in value) return value.toDate();
    else if ('calendar' in value) return value.toDate(timeZone);
    else if (!(value instanceof Date)) return $4f61dbf9ff82452c$var$convertValue(value).toDate(timeZone);
    return value;
}
function $4f61dbf9ff82452c$var$getDefaultFormatOptions(value) {
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
function $4f61dbf9ff82452c$var$convertValue(value) {
    let date = (0, $jN8cE$today)((0, $jN8cE$getLocalTimeZone)());
    return (0, $jN8cE$toCalendarDateTime)(date, value);
}


export {$4f61dbf9ff82452c$export$d1328f67a56fa517 as LabeledValue};
//# sourceMappingURL=LabeledValue.js.map
