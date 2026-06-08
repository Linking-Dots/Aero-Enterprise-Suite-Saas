import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "./styles.css";
import $fgkol$styles_cssmjs from "./styles_css.mjs";
import $fgkol$react, {useRef as $fgkol$useRef} from "react";
import {useDateSegment as $fgkol$useDateSegment} from "react-aria/useDateField";


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



function $8fe48145f400a5cb$export$6388987c5223b54e({ segment: segment, state: state, ...otherProps }) {
    switch(segment.type){
        // A separator, e.g. punctuation
        case 'literal':
            return /*#__PURE__*/ (0, $fgkol$react).createElement($8fe48145f400a5cb$var$LiteralSegment, {
                segment: segment
            });
        // Editable segment
        default:
            return /*#__PURE__*/ (0, $fgkol$react).createElement($8fe48145f400a5cb$var$EditableSegment, {
                segment: segment,
                state: state,
                ...otherProps
            });
    }
}
function $8fe48145f400a5cb$var$LiteralSegment({ segment: segment }) {
    return /*#__PURE__*/ (0, $fgkol$react).createElement("span", {
        "aria-hidden": "true",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fgkol$styles_cssmjs))), 'react-spectrum-Datepicker-literal'),
        "data-testid": segment.type === 'literal' ? undefined : segment.type
    }, segment.text);
}
function $8fe48145f400a5cb$var$EditableSegment({ segment: segment, state: state }) {
    let ref = (0, $fgkol$useRef)(null);
    let { segmentProps: segmentProps } = (0, $fgkol$useDateSegment)(segment, state, ref);
    return /*#__PURE__*/ (0, $fgkol$react).createElement("span", {
        ...segmentProps,
        ref: ref,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fgkol$styles_cssmjs))), 'react-spectrum-DatePicker-cell', {
            'is-placeholder': segment.isPlaceholder,
            'is-read-only': !segment.isEditable
        }),
        style: segmentProps.style,
        "data-testid": segment.type
    }, segment.isPlaceholder ? /*#__PURE__*/ (0, $fgkol$react).createElement("span", {
        "aria-hidden": "true",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fgkol$styles_cssmjs))), 'react-spectrum-DatePicker-placeholder')
    }, segment.placeholder) : segment.text);
}


export {$8fe48145f400a5cb$export$6388987c5223b54e as DatePickerSegment};
//# sourceMappingURL=DatePickerSegment.mjs.map
