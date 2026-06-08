var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $d6479700d21b596b$exports = require("../layout/Grid.cjs");
require("./styles.css");
var $9730d29fe3ac43ea$exports = require("./styles_css.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $15e3b68ec42125a9$exports = require("../text/Text.cjs");
var $eSjne$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DragPreview", function () { return $bd2ba47d5f5b19a2$export$905ab40ac2179daa; });
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





function $bd2ba47d5f5b19a2$export$905ab40ac2179daa(props) {
    let { item: item, itemCount: itemCount, itemHeight: itemHeight, density: density } = props;
    let isDraggingMultiple = itemCount > 1;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($eSjne$react))).createElement("div", {
        style: {
            height: itemHeight
        },
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($9730d29fe3ac43ea$exports))), 'react-spectrum-ListViewItem', 'react-spectrum-ListViewItem-dragPreview', {
            'react-spectrum-ListViewItem-dragPreview--multiple': isDraggingMultiple
        }, `react-spectrum-ListViewItem-dragPreview--${density}`)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($eSjne$react))).createElement((0, $d6479700d21b596b$exports.Grid), {
        UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-grid']
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($eSjne$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            text: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-content']
            },
            description: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-description']
            },
            illustration: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-thumbnail']
            },
            image: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-thumbnail']
            },
            actionButton: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-actions'],
                isQuiet: true
            },
            actionGroup: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-actions'],
                isQuiet: true,
                density: 'compact'
            },
            actionMenu: {
                UNSAFE_className: (0, ($parcel$interopDefault($9730d29fe3ac43ea$exports)))['react-spectrum-ListViewItem-actionmenu'],
                isQuiet: true
            }
        }
    }, typeof item.rendered === 'string' ? /*#__PURE__*/ (0, ($parcel$interopDefault($eSjne$react))).createElement((0, $15e3b68ec42125a9$exports.Text), null, item.rendered) : item.rendered, isDraggingMultiple && /*#__PURE__*/ (0, ($parcel$interopDefault($eSjne$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($9730d29fe3ac43ea$exports))), 'react-spectrum-ListViewItem-badge')
    }, itemCount))));
}


//# sourceMappingURL=DragPreview.cjs.map
