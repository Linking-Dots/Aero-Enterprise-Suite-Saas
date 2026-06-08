var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("./styles.css");
var $25dd6e69bdd309d3$exports = require("./styles_css.cjs");
var $8LozA$react = require("react");
var $8LozA$reactariauseDateField = require("react-aria/useDateField");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DatePickerSegment", function () { return $9b919f07381d65d4$export$6388987c5223b54e; });
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



function $9b919f07381d65d4$export$6388987c5223b54e({ segment: segment, state: state, ...otherProps }) {
    switch(segment.type){
        // A separator, e.g. punctuation
        case 'literal':
            return /*#__PURE__*/ (0, ($parcel$interopDefault($8LozA$react))).createElement($9b919f07381d65d4$var$LiteralSegment, {
                segment: segment
            });
        // Editable segment
        default:
            return /*#__PURE__*/ (0, ($parcel$interopDefault($8LozA$react))).createElement($9b919f07381d65d4$var$EditableSegment, {
                segment: segment,
                state: state,
                ...otherProps
            });
    }
}
function $9b919f07381d65d4$var$LiteralSegment({ segment: segment }) {
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8LozA$react))).createElement("span", {
        "aria-hidden": "true",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-Datepicker-literal'),
        "data-testid": segment.type === 'literal' ? undefined : segment.type
    }, segment.text);
}
function $9b919f07381d65d4$var$EditableSegment({ segment: segment, state: state }) {
    let ref = (0, $8LozA$react.useRef)(null);
    let { segmentProps: segmentProps } = (0, $8LozA$reactariauseDateField.useDateSegment)(segment, state, ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8LozA$react))).createElement("span", {
        ...segmentProps,
        ref: ref,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-DatePicker-cell', {
            'is-placeholder': segment.isPlaceholder,
            'is-read-only': !segment.isEditable
        }),
        style: segmentProps.style,
        "data-testid": segment.type
    }, segment.isPlaceholder ? /*#__PURE__*/ (0, ($parcel$interopDefault($8LozA$react))).createElement("span", {
        "aria-hidden": "true",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($25dd6e69bdd309d3$exports))), 'react-spectrum-DatePicker-placeholder')
    }, segment.placeholder) : segment.text);
}


//# sourceMappingURL=DatePickerSegment.cjs.map
