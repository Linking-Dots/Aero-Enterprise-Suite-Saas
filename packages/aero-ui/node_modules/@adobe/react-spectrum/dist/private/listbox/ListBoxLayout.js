import {Rect as $2gU45$Rect, LayoutInfo as $2gU45$LayoutInfo, ListLayout as $2gU45$ListLayout} from "react-stately/useVirtualizerState";


class $83307db3d5713315$export$c7e5f5ea00052bf extends (0, $2gU45$ListLayout) {
    update(invalidationContext) {
        var _invalidationContext_layoutOptions;
        this.isLoading = ((_invalidationContext_layoutOptions = invalidationContext.layoutOptions) === null || _invalidationContext_layoutOptions === void 0 ? void 0 : _invalidationContext_layoutOptions.isLoading) || false;
        super.update(invalidationContext);
    }
    buildCollection() {
        let nodes = super.buildCollection(this.paddingY);
        let y = this.contentSize.height;
        if (this.isLoading) {
            let rect = new (0, $2gU45$Rect)(0, y, this.virtualizer.visibleRect.width, 40);
            let loader = new (0, $2gU45$LayoutInfo)('loader', 'loader', rect);
            let node = {
                layoutInfo: loader,
                validRect: loader.rect
            };
            nodes.push(node);
            this.layoutNodes.set(loader.key, node);
            y = loader.rect.maxY;
        }
        if (nodes.length === 0) {
            var _this_placeholderHeight;
            let rect = new (0, $2gU45$Rect)(0, y, this.virtualizer.visibleRect.width, (_this_placeholderHeight = this.placeholderHeight) !== null && _this_placeholderHeight !== void 0 ? _this_placeholderHeight : this.virtualizer.visibleRect.height);
            let placeholder = new (0, $2gU45$LayoutInfo)('placeholder', 'placeholder', rect);
            let node = {
                layoutInfo: placeholder,
                validRect: placeholder.rect
            };
            nodes.push(node);
            this.layoutNodes.set(placeholder.key, node);
            y = placeholder.rect.maxY;
        }
        this.contentSize.height = y + this.paddingY;
        return nodes;
    }
    buildSection(node, x, y) {
        // Synthesize a collection node for the header.
        let headerNode = {
            type: 'header',
            key: node.key + ':header',
            parentKey: node.key,
            value: null,
            level: node.level,
            index: node.index,
            hasChildNodes: false,
            childNodes: [],
            rendered: node.rendered,
            textValue: node.textValue
        };
        // Build layout node for it and adjust y offset of section children.
        let header = this.buildSectionHeader(headerNode, x, y);
        header.node = headerNode;
        header.layoutInfo.parentKey = node.key;
        this.layoutNodes.set(headerNode.key, header);
        y += header.layoutInfo.rect.height;
        let section = super.buildSection(node, x, y);
        section.children.unshift(header);
        return section;
    }
    constructor(opts){
        super(opts), this.isLoading = false;
        this.placeholderHeight = opts.placeholderHeight;
        this.paddingY = opts.paddingY;
    }
}


export {$83307db3d5713315$export$c7e5f5ea00052bf as ListBoxLayout};
//# sourceMappingURL=ListBoxLayout.js.map
