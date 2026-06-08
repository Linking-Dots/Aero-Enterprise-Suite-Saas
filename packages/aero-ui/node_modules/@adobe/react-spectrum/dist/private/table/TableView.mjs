import {TableViewWithoutExpanding as $50d34b48263523ea$export$c488dd5235f7fcac} from "./TableViewWithoutExpanding.mjs";
import {TreeGridTableView as $f959523cd41c2546$export$5669566ac2c90964} from "./TreeGridTableView.mjs";
import {Column as $kAGEk$Column, Cell as $df4bcb48368399d8$re_export$Cell, Row as $df4bcb48368399d8$re_export$Row, TableBody as $df4bcb48368399d8$re_export$TableBody, TableHeader as $df4bcb48368399d8$re_export$TableHeader} from "react-stately/useTableState";
import $kAGEk$react from "react";
import {Section as $df4bcb48368399d8$re_export$Section} from "react-stately/Section";
import {tableNestedRows as $kAGEk$tableNestedRows} from "react-stately/private/flags/flags";

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
 */ const $df4bcb48368399d8$export$b3c27e869d856b7 = /*#__PURE__*/ (0, $kAGEk$react).forwardRef(function TableView(props, ref) {
    let { UNSTABLE_allowsExpandableRows: UNSTABLE_allowsExpandableRows, ...otherProps } = props;
    if ((0, $kAGEk$tableNestedRows)() && UNSTABLE_allowsExpandableRows) return /*#__PURE__*/ (0, $kAGEk$react).createElement((0, $f959523cd41c2546$export$5669566ac2c90964), {
        ...otherProps,
        ref: ref
    });
    else return /*#__PURE__*/ (0, $kAGEk$react).createElement((0, $50d34b48263523ea$export$c488dd5235f7fcac), {
        ...otherProps,
        ref: ref
    });
});
// Override TS for Column to support spectrum specific props.
const $df4bcb48368399d8$export$816b5d811295e6bc = (0, $kAGEk$Column);


export {$df4bcb48368399d8$export$b3c27e869d856b7 as TableView, $df4bcb48368399d8$export$816b5d811295e6bc as Column, $df4bcb48368399d8$re_export$Cell as Cell, $df4bcb48368399d8$re_export$Row as Row, $df4bcb48368399d8$re_export$Section as Section, $df4bcb48368399d8$re_export$TableBody as TableBody, $df4bcb48368399d8$re_export$TableHeader as TableHeader};
//# sourceMappingURL=TableView.mjs.map
