var $8WLeo$reactstatelyuseVirtualizerState = require("react-stately/useVirtualizerState");


function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ListBoxLayout", function () { return $b660b0e98da9950e$export$c7e5f5ea00052bf; });

class $b660b0e98da9950e$export$c7e5f5ea00052bf extends (0, $8WLeo$reactstatelyuseVirtualizerState.ListLayout) {
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
            let rect = new (0, $8WLeo$reactstatelyuseVirtualizerState.Rect)(0, y, this.virtualizer.visibleRect.width, 40);
            let loader = new (0, $8WLeo$reactstatelyuseVirtualizerState.LayoutInfo)('loader', 'loader', rect);
            let node = {
                layoutInfo: loader,
                validRect: loader.rect
            };
            nodes.push(node);
            this.layoutNodes.set(loader.key, node);
            y = loader.rect.maxY;
        }
        if (nodes.length === 0) {
            let rect = new (0, $8WLeo$reactstatelyuseVirtualizerState.Rect)(0, y, this.virtualizer.visibleRect.width, this.placeholderHeight ?? this.virtualizer.visibleRect.height);
            let placeholder = new (0, $8WLeo$reactstatelyuseVirtualizerState.LayoutInfo)('placeholder', 'placeholder', rect);
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


//# sourceMappingURL=ListBoxLayout.cjs.map
