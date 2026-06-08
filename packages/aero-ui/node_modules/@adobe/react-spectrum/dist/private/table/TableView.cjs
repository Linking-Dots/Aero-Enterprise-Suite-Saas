var $cec3453498a09f0b$exports = require("./TableViewWithoutExpanding.cjs");
var $c78003d825dbbef1$exports = require("./TreeGridTableView.cjs");
var $j2RSU$reactstatelyuseTableState = require("react-stately/useTableState");
var $j2RSU$react = require("react");
var $j2RSU$reactstatelySection = require("react-stately/Section");
var $j2RSU$reactstatelyprivateflagsflags = require("react-stately/private/flags/flags");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TableView", function () { return $d057c689790b84e8$export$b3c27e869d856b7; });
$parcel$export(module.exports, "Column", function () { return $d057c689790b84e8$export$816b5d811295e6bc; });
$parcel$export(module.exports, "Cell", function () { return $j2RSU$reactstatelyuseTableState.Cell; });
$parcel$export(module.exports, "Row", function () { return $j2RSU$reactstatelyuseTableState.Row; });
$parcel$export(module.exports, "Section", function () { return $j2RSU$reactstatelySection.Section; });
$parcel$export(module.exports, "TableBody", function () { return $j2RSU$reactstatelyuseTableState.TableBody; });
$parcel$export(module.exports, "TableHeader", function () { return $j2RSU$reactstatelyuseTableState.TableHeader; });
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





/**
 * Tables are containers for displaying information. They allow users to quickly scan, sort,
 * compare, and take action on large amounts of data.
 */ const $d057c689790b84e8$export$b3c27e869d856b7 = /*#__PURE__*/ (0, ($parcel$interopDefault($j2RSU$react))).forwardRef(function TableView(props, ref) {
    let { UNSTABLE_allowsExpandableRows: UNSTABLE_allowsExpandableRows, ...otherProps } = props;
    if ((0, $j2RSU$reactstatelyprivateflagsflags.tableNestedRows)() && UNSTABLE_allowsExpandableRows) return /*#__PURE__*/ (0, ($parcel$interopDefault($j2RSU$react))).createElement((0, $c78003d825dbbef1$exports.TreeGridTableView), {
        ...otherProps,
        ref: ref
    });
    else return /*#__PURE__*/ (0, ($parcel$interopDefault($j2RSU$react))).createElement((0, $cec3453498a09f0b$exports.TableViewWithoutExpanding), {
        ...otherProps,
        ref: ref
    });
});
// Override TS for Column to support spectrum specific props.
const $d057c689790b84e8$export$816b5d811295e6bc = (0, $j2RSU$reactstatelyuseTableState.Column);


//# sourceMappingURL=TableView.cjs.map
