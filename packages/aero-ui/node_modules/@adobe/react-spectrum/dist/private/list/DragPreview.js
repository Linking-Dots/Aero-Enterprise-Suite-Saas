import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Grid as $727c1a1d9e8b8d73$export$ef2184bd89960b14} from "../layout/Grid.js";
import "./styles.css";
import $7YqcK$styles_cssmjs from "./styles_css.mjs";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import $7YqcK$react from "react";


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





function $bf4b311ad7dda766$export$905ab40ac2179daa(props) {
    let { item: item, itemCount: itemCount, itemHeight: itemHeight, density: density } = props;
    let isDraggingMultiple = itemCount > 1;
    return /*#__PURE__*/ (0, $7YqcK$react).createElement("div", {
        style: {
            height: itemHeight
        },
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7YqcK$styles_cssmjs))), 'react-spectrum-ListViewItem', 'react-spectrum-ListViewItem-dragPreview', {
            'react-spectrum-ListViewItem-dragPreview--multiple': isDraggingMultiple
        }, `react-spectrum-ListViewItem-dragPreview--${density}`)
    }, /*#__PURE__*/ (0, $7YqcK$react).createElement((0, $727c1a1d9e8b8d73$export$ef2184bd89960b14), {
        UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-grid']
    }, /*#__PURE__*/ (0, $7YqcK$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            text: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-content']
            },
            description: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-description']
            },
            illustration: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-thumbnail']
            },
            image: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-thumbnail']
            },
            actionButton: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-actions'],
                isQuiet: true
            },
            actionGroup: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-actions'],
                isQuiet: true,
                density: 'compact'
            },
            actionMenu: {
                UNSAFE_className: (0, ($parcel$interopDefault($7YqcK$styles_cssmjs)))['react-spectrum-ListViewItem-actionmenu'],
                isQuiet: true
            }
        }
    }, typeof item.rendered === 'string' ? /*#__PURE__*/ (0, $7YqcK$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, item.rendered) : item.rendered, isDraggingMultiple && /*#__PURE__*/ (0, $7YqcK$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7YqcK$styles_cssmjs))), 'react-spectrum-ListViewItem-badge')
    }, itemCount))));
}


export {$bf4b311ad7dda766$export$905ab40ac2179daa as DragPreview};
//# sourceMappingURL=DragPreview.js.map
