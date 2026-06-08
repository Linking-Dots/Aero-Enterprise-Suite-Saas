var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $e04035822dddb314$exports = require("../layout/Flex.cjs");
require("../table_vars.css");
var $149bebff1c2de463$exports = require("../table_vars_css.cjs");
require("./table.css");
var $a82a0782618e719b$exports = require("./table_css.cjs");
var $8gWrD$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DragPreview", function () { return $cda8e47169650f6b$export$905ab40ac2179daa; });
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 




function $cda8e47169650f6b$export$905ab40ac2179daa(props) {
    let { itemText: itemText, itemCount: itemCount, height: height, maxWidth: maxWidth } = props;
    let isDraggingMultiple = itemCount > 1;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8gWrD$react))).createElement((0, $e04035822dddb314$exports.Flex), {
        justifyContent: "space-between",
        height: height,
        maxWidth: maxWidth,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($149bebff1c2de463$exports))), 'spectrum-Table-row', (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($a82a0782618e719b$exports))), 'react-spectrum-Table-row', 'react-spectrum-Table-row-dragPreview', {
            'react-spectrum-Table-row-dragPreview--multiple': isDraggingMultiple
        }))
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8gWrD$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($149bebff1c2de463$exports))), 'spectrum-Table-cell', (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($a82a0782618e719b$exports))), 'react-spectrum-Table-cell'))
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8gWrD$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($149bebff1c2de463$exports))), 'spectrum-Table-cellContents')
    }, itemText)), isDraggingMultiple && /*#__PURE__*/ (0, ($parcel$interopDefault($8gWrD$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($a82a0782618e719b$exports))), 'react-spectrum-Table-row-badge')
    }, itemCount));
}


//# sourceMappingURL=DragPreview.cjs.map
