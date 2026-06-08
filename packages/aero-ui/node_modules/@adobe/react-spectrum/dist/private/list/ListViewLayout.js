import {Rect as $83tgv$Rect, LayoutInfo as $83tgv$LayoutInfo, ListLayout as $83tgv$ListLayout} from "react-stately/useVirtualizerState";

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
class $d5bbdf752e3f3896$export$dab781655dfbb7d3 extends (0, $83tgv$ListLayout) {
    update(invalidationContext) {
        var _invalidationContext_layoutOptions;
        this.isLoading = ((_invalidationContext_layoutOptions = invalidationContext.layoutOptions) === null || _invalidationContext_layoutOptions === void 0 ? void 0 : _invalidationContext_layoutOptions.isLoading) || false;
        super.update(invalidationContext);
    }
    buildCollection() {
        let nodes = super.buildCollection();
        let y = this.contentSize.height;
        if (this.isLoading) {
            var _this_estimatedRowHeight;
            let rect = new (0, $83tgv$Rect)(0, y, this.virtualizer.visibleRect.width, nodes.length === 0 ? this.virtualizer.visibleRect.height : (_this_estimatedRowHeight = this.estimatedRowHeight) !== null && _this_estimatedRowHeight !== void 0 ? _this_estimatedRowHeight : 48);
            let loader = new (0, $83tgv$LayoutInfo)('loader', 'loader', rect);
            let node = {
                layoutInfo: loader,
                validRect: loader.rect
            };
            nodes.push(node);
            this.layoutNodes.set(loader.key, node);
            y = loader.rect.maxY;
        }
        if (nodes.length === 0) {
            let rect = new (0, $83tgv$Rect)(0, y, this.virtualizer.visibleRect.width, this.virtualizer.visibleRect.height);
            let placeholder = new (0, $83tgv$LayoutInfo)('placeholder', 'placeholder', rect);
            let node = {
                layoutInfo: placeholder,
                validRect: placeholder.rect
            };
            nodes.push(node);
            this.layoutNodes.set(placeholder.key, node);
            y = placeholder.rect.maxY;
        }
        this.contentSize.height = y;
        return nodes;
    }
    buildItem(node, x, y) {
        let res = super.buildItem(node, x, y);
        // allow overflow so the focus ring/selection ring can extend outside to overlap with the adjacent items borders
        res.layoutInfo.allowOverflow = true;
        return res;
    }
    constructor(...args){
        super(...args), this.isLoading = false;
    }
}


export {$d5bbdf752e3f3896$export$dab781655dfbb7d3 as ListViewLayout};
//# sourceMappingURL=ListViewLayout.js.map
