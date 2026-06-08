import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Grid as $572f9fec526c2697$export$ef2184bd89960b14} from "../layout/Grid.mjs";
import "./styles.css";
import $hoE6r$styles_cssmjs from "./styles_css.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import $hoE6r$react from "react";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2021 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 





function $7e0b158902559b5b$export$905ab40ac2179daa(props) {
    let { item: item, itemCount: itemCount, itemHeight: itemHeight, density: density } = props;
    let isDraggingMultiple = itemCount > 1;
    return /*#__PURE__*/ (0, $hoE6r$react).createElement("div", {
        style: {
            height: itemHeight
        },
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hoE6r$styles_cssmjs))), 'react-spectrum-ListViewItem', 'react-spectrum-ListViewItem-dragPreview', {
            'react-spectrum-ListViewItem-dragPreview--multiple': isDraggingMultiple
        }, `react-spectrum-ListViewItem-dragPreview--${density}`)
    }, /*#__PURE__*/ (0, $hoE6r$react).createElement((0, $572f9fec526c2697$export$ef2184bd89960b14), {
        UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-grid']
    }, /*#__PURE__*/ (0, $hoE6r$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            text: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-content']
            },
            description: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-description']
            },
            illustration: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-thumbnail']
            },
            image: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-thumbnail']
            },
            actionButton: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-actions'],
                isQuiet: true
            },
            actionGroup: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-actions'],
                isQuiet: true,
                density: 'compact'
            },
            actionMenu: {
                UNSAFE_className: (0, ($parcel$interopDefault($hoE6r$styles_cssmjs)))['react-spectrum-ListViewItem-actionmenu'],
                isQuiet: true
            }
        }
    }, typeof item.rendered === 'string' ? /*#__PURE__*/ (0, $hoE6r$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, item.rendered) : item.rendered, isDraggingMultiple && /*#__PURE__*/ (0, $hoE6r$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hoE6r$styles_cssmjs))), 'react-spectrum-ListViewItem-badge')
    }, itemCount))));
}


export {$7e0b158902559b5b$export$905ab40ac2179daa as DragPreview};
//# sourceMappingURL=DragPreview.mjs.map
