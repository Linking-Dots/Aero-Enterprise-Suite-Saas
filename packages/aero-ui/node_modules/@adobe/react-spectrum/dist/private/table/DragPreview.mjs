import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Flex as $ec3baf921918e057$export$f51f4c4ede09e011} from "../layout/Flex.mjs";
import "../table_vars.css";
import $c7eeA$table_vars_cssmjs from "../table_vars_css.mjs";
import "./table.css";
import $c7eeA$table_cssmjs from "./table_css.mjs";
import $c7eeA$react from "react";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
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




function $98bc3ab36527e684$export$905ab40ac2179daa(props) {
    let { itemText: itemText, itemCount: itemCount, height: height, maxWidth: maxWidth } = props;
    let isDraggingMultiple = itemCount > 1;
    return /*#__PURE__*/ (0, $c7eeA$react).createElement((0, $ec3baf921918e057$export$f51f4c4ede09e011), {
        justifyContent: "space-between",
        height: height,
        maxWidth: maxWidth,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c7eeA$table_vars_cssmjs))), 'spectrum-Table-row', (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c7eeA$table_cssmjs))), 'react-spectrum-Table-row', 'react-spectrum-Table-row-dragPreview', {
            'react-spectrum-Table-row-dragPreview--multiple': isDraggingMultiple
        }))
    }, /*#__PURE__*/ (0, $c7eeA$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c7eeA$table_vars_cssmjs))), 'spectrum-Table-cell', (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c7eeA$table_cssmjs))), 'react-spectrum-Table-cell'))
    }, /*#__PURE__*/ (0, $c7eeA$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c7eeA$table_vars_cssmjs))), 'spectrum-Table-cellContents')
    }, itemText)), isDraggingMultiple && /*#__PURE__*/ (0, $c7eeA$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c7eeA$table_cssmjs))), 'react-spectrum-Table-row-badge')
    }, itemCount));
}


export {$98bc3ab36527e684$export$905ab40ac2179daa as DragPreview};
//# sourceMappingURL=DragPreview.mjs.map
