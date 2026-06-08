import {Rect as $1HXk1$Rect, LayoutInfo as $1HXk1$LayoutInfo, TableLayout as $1HXk1$TableLayout} from "react-stately/useVirtualizerState";

/*
 * Copyright 2024 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 
class $f6b4d51569b075f9$export$725d101278f5a47b extends (0, $1HXk1$TableLayout) {
    buildCollection() {
        let collection = this.virtualizer.collection;
        let loadingState = collection.body.props.loadingState;
        this.isLoading = loadingState === 'loading' || loadingState === 'loadingMore';
        return super.buildCollection();
    }
    buildColumn(node, x, y) {
        let res = super.buildColumn(node, x, y);
        res.layoutInfo.allowOverflow = true; // for resizer nubbin
        return res;
    }
    buildBody() {
        let node = super.buildBody(0);
        let { children: children, layoutInfo: layoutInfo } = node;
        if (!children) throw new Error('Missing children in LayoutInfo');
        let width = node.layoutInfo.rect.width;
        if (this.isLoading) {
            // Add some margin around the loader to ensure that scrollbars don't flicker in and out.
            let rect = new (0, $1HXk1$Rect)(40, children.length === 0 ? 40 : layoutInfo.rect.maxY, (width || this.virtualizer.visibleRect.width) - 80, children.length === 0 ? this.virtualizer.visibleRect.height - 80 : 60);
            let loader = new (0, $1HXk1$LayoutInfo)('loader', 'loader', rect);
            loader.parentKey = layoutInfo.key;
            loader.isSticky = children.length === 0;
            let node = {
                layoutInfo: loader,
                validRect: loader.rect
            };
            children.push(node);
            this.layoutNodes.set(loader.key, node);
            layoutInfo.rect.height = loader.rect.maxY;
            width = Math.max(width, rect.width);
        } else if (children.length === 0) {
            let rect = new (0, $1HXk1$Rect)(40, 40, this.virtualizer.visibleRect.width - 80, this.virtualizer.visibleRect.height - 80);
            let empty = new (0, $1HXk1$LayoutInfo)('empty', 'empty', rect);
            empty.parentKey = layoutInfo.key;
            empty.isSticky = true;
            empty.allowOverflow = true;
            let node = {
                layoutInfo: empty,
                validRect: empty.rect
            };
            children.push(node);
            layoutInfo.rect.height = empty.rect.maxY;
            width = Math.max(width, rect.width);
        }
        return node;
    }
    buildRow(node, x, y) {
        let res = super.buildRow(node, x, y);
        res.layoutInfo.rect.height += 1; // for bottom border
        return res;
    }
    buildCell(node, x, y) {
        var _node_column;
        let res = super.buildCell(node, x, y);
        if ((_node_column = node.column) === null || _node_column === void 0 ? void 0 : _node_column.props.hideHeader) res.layoutInfo.allowOverflow = true;
        return res;
    }
    getEstimatedRowHeight() {
        return super.getEstimatedRowHeight() + 1; // for bottom border
    }
    isStickyColumn(node) {
        var _node_props, _node_props1;
        var _ref;
        return (_ref = ((_node_props = node.props) === null || _node_props === void 0 ? void 0 : _node_props.isDragButtonCell) || ((_node_props1 = node.props) === null || _node_props1 === void 0 ? void 0 : _node_props1.isSelectionCell)) !== null && _ref !== void 0 ? _ref : false;
    }
    getDropTargetFromPoint(x, y, isValidDropTarget) {
        var _this_getVisibleLayoutInfos_find;
        var _this_getVisibleLayoutInfos_find_rect_height;
        // Offset for height of header row
        y -= (_this_getVisibleLayoutInfos_find_rect_height = (_this_getVisibleLayoutInfos_find = this.getVisibleLayoutInfos(new (0, $1HXk1$Rect)(x, y, 1, 1)).find((info)=>info.type === 'headerrow')) === null || _this_getVisibleLayoutInfos_find === void 0 ? void 0 : _this_getVisibleLayoutInfos_find.rect.height) !== null && _this_getVisibleLayoutInfos_find_rect_height !== void 0 ? _this_getVisibleLayoutInfos_find_rect_height : 0;
        return super.getDropTargetFromPoint(x, y, isValidDropTarget);
    }
    constructor(...args){
        super(...args), this.isLoading = false;
    }
}


export {$f6b4d51569b075f9$export$725d101278f5a47b as TableViewLayout};
//# sourceMappingURL=TableViewLayout.js.map
