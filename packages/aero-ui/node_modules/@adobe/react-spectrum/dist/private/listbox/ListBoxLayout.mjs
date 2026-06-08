import {Rect as $fX8lG$Rect, LayoutInfo as $fX8lG$LayoutInfo, ListLayout as $fX8lG$ListLayout} from "react-stately/useVirtualizerState";


class $1cf0b89a33b93e55$export$c7e5f5ea00052bf extends (0, $fX8lG$ListLayout) {
    constructor(opts){
        super(opts), this.isLoading = false;
        this.placeholderHeight = opts.placeholderHeight;
        this.paddingY = opts.paddingY;
    }
    update(invalidationContext) {
        this.isLoading = invalidationContext.layoutOptions?.isLoading || false;
        super.update(invalidationContext);
    }
    buildCollection() {
        let nodes = super.buildCollection(this.paddingY);
        let y = this.contentSize.height;
        if (this.isLoading) {
            let rect = new (0, $fX8lG$Rect)(0, y, this.virtualizer.visibleRect.width, 40);
            let loader = new (0, $fX8lG$LayoutInfo)('loader', 'loader', rect);
            let node = {
                layoutInfo: loader,
                validRect: loader.rect
            };
            nodes.push(node);
            this.layoutNodes.set(loader.key, node);
            y = loader.rect.maxY;
        }
        if (nodes.length === 0) {
            let rect = new (0, $fX8lG$Rect)(0, y, this.virtualizer.visibleRect.width, this.placeholderHeight ?? this.virtualizer.visibleRect.height);
            let placeholder = new (0, $fX8lG$LayoutInfo)('placeholder', 'placeholder', rect);
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
}


export {$1cf0b89a33b93e55$export$c7e5f5ea00052bf as ListBoxLayout};
//# sourceMappingURL=ListBoxLayout.mjs.map
