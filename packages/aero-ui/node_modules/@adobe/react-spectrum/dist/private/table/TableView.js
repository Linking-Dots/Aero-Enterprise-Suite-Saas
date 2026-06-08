import {TableViewWithoutExpanding as $afff8b74e9314c77$export$c488dd5235f7fcac} from "./TableViewWithoutExpanding.js";
import {TreeGridTableView as $7f56daa9665ee6c8$export$5669566ac2c90964} from "./TreeGridTableView.js";
import {Column as $8jFf8$Column, Cell as $e9d8704413caaded$re_export$Cell, Row as $e9d8704413caaded$re_export$Row, TableBody as $e9d8704413caaded$re_export$TableBody, TableHeader as $e9d8704413caaded$re_export$TableHeader} from "react-stately/useTableState";
import $8jFf8$react from "react";
import {Section as $e9d8704413caaded$re_export$Section} from "react-stately/Section";
import {tableNestedRows as $8jFf8$tableNestedRows} from "react-stately/private/flags/flags";

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
 */ const $e9d8704413caaded$export$b3c27e869d856b7 = /*#__PURE__*/ (0, $8jFf8$react).forwardRef(function TableView(props, ref) {
    let { UNSTABLE_allowsExpandableRows: UNSTABLE_allowsExpandableRows, ...otherProps } = props;
    if ((0, $8jFf8$tableNestedRows)() && UNSTABLE_allowsExpandableRows) return /*#__PURE__*/ (0, $8jFf8$react).createElement((0, $7f56daa9665ee6c8$export$5669566ac2c90964), {
        ...otherProps,
        ref: ref
    });
    else return /*#__PURE__*/ (0, $8jFf8$react).createElement((0, $afff8b74e9314c77$export$c488dd5235f7fcac), {
        ...otherProps,
        ref: ref
    });
});
// Override TS for Column to support spectrum specific props.
const $e9d8704413caaded$export$816b5d811295e6bc = (0, $8jFf8$Column);


export {$e9d8704413caaded$export$b3c27e869d856b7 as TableView, $e9d8704413caaded$export$816b5d811295e6bc as Column, $e9d8704413caaded$re_export$Cell as Cell, $e9d8704413caaded$re_export$Row as Row, $e9d8704413caaded$re_export$Section as Section, $e9d8704413caaded$re_export$TableBody as TableBody, $e9d8704413caaded$re_export$TableHeader as TableHeader};
//# sourceMappingURL=TableView.js.map
