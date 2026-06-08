import {createBranchComponent as $5TgV3$createBranchComponent} from "react-aria/CollectionBuilder";
import $5TgV3$react, {createContext as $5TgV3$createContext, useContext as $5TgV3$useContext, isValidElement as $5TgV3$isValidElement, cloneElement as $5TgV3$cloneElement, useMemo as $5TgV3$useMemo} from "react";
import {useCachedChildren as $5TgV3$useCachedChildren} from "react-aria/private/collections/useCachedChildren";

/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


const $a53f0f6636929daa$export$d40e14dec8b060a8 = /*#__PURE__*/ (0, $5TgV3$createContext)(null);
const $a53f0f6636929daa$export$6e2c8f0811a474ce = /*#__PURE__*/ (0, $5TgV3$createBranchComponent)('section', (props, ref, section)=>{
    let { name: name, render: render } = (0, $5TgV3$useContext)($a53f0f6636929daa$export$d40e14dec8b060a8);
    if (process.env.NODE_ENV !== 'production') console.warn(`<Section> is deprecated. Please use <${name}> instead.`);
    return render(props, ref, section, 'react-aria-Section');
});
const $a53f0f6636929daa$export$a164736487e3f0ae = {
    CollectionRoot ({ collection: collection, renderDropIndicator: renderDropIndicator }) {
        return $a53f0f6636929daa$var$useCollectionRender(collection, null, renderDropIndicator);
    },
    CollectionBranch ({ collection: collection, parent: parent, renderDropIndicator: renderDropIndicator }) {
        return $a53f0f6636929daa$var$useCollectionRender(collection, parent, renderDropIndicator);
    }
};
function $a53f0f6636929daa$var$useCollectionRender(collection, parent, renderDropIndicator) {
    return (0, $5TgV3$useCachedChildren)({
        items: parent ? collection.getChildren(parent.key) : collection,
        dependencies: [
            renderDropIndicator
        ],
        children (node) {
            // Return a empty fragment since we don't want to render the content twice
            // If we don't skip the content node here, we end up rendering them twice in a Tree since we also render the content node in TreeItem
            if (node.type === 'content') return /*#__PURE__*/ (0, $5TgV3$react).createElement((0, $5TgV3$react).Fragment, null);
            let rendered = node.render(node);
            if (!renderDropIndicator || node.type !== 'item') return rendered;
            return /*#__PURE__*/ (0, $5TgV3$react).createElement((0, $5TgV3$react).Fragment, null, renderDropIndicator({
                type: 'item',
                key: node.key,
                dropPosition: 'before'
            }), rendered, $a53f0f6636929daa$export$2dbbd341daed716d(collection, node, renderDropIndicator));
        }
    });
}
function $a53f0f6636929daa$export$2dbbd341daed716d(collection, node, renderDropIndicator) {
    let key = node.key;
    let keyAfter = collection.getKeyAfter(key);
    let nextItemInFlattenedCollection = keyAfter != null ? collection.getItem(keyAfter) : null;
    while(nextItemInFlattenedCollection != null && nextItemInFlattenedCollection.type !== 'item'){
        keyAfter = collection.getKeyAfter(nextItemInFlattenedCollection.key);
        nextItemInFlattenedCollection = keyAfter != null ? collection.getItem(keyAfter) : null;
    }
    let nextItemInSameLevel = node.nextKey != null ? collection.getItem(node.nextKey) : null;
    while(nextItemInSameLevel != null && nextItemInSameLevel.type !== 'item')nextItemInSameLevel = nextItemInSameLevel.nextKey != null ? collection.getItem(nextItemInSameLevel.nextKey) : null;
    // Render one or more "after" drop indicators when the next item in the flattened collection
    // has a smaller level, is not an item, or there are no more items in the collection.
    // Otherwise, the "after" position is equivalent to the next item's "before" position.
    let afterIndicators = [];
    if (nextItemInSameLevel == null) {
        let current = node;
        while((current === null || current === void 0 ? void 0 : current.type) === 'item' && (!nextItemInFlattenedCollection || current.parentKey !== nextItemInFlattenedCollection.parentKey && nextItemInFlattenedCollection.level < current.level)){
            let indicator = renderDropIndicator({
                type: 'item',
                key: current.key,
                dropPosition: 'after'
            });
            if (/*#__PURE__*/ (0, $5TgV3$isValidElement)(indicator)) afterIndicators.push(/*#__PURE__*/ (0, $5TgV3$cloneElement)(indicator, {
                key: `${current.key}-after`
            }));
            current = current.parentKey != null ? collection.getItem(current.parentKey) : null;
        }
    }
    return afterIndicators;
}
const $a53f0f6636929daa$export$4feb769f8ddf26c5 = /*#__PURE__*/ (0, $5TgV3$createContext)($a53f0f6636929daa$export$a164736487e3f0ae);
function $a53f0f6636929daa$export$90e00781bc59d8f9(focusedKey) {
    return (0, $5TgV3$useMemo)(()=>focusedKey != null ? new Set([
            focusedKey
        ]) : null, [
        focusedKey
    ]);
}


export {$a53f0f6636929daa$export$d40e14dec8b060a8 as SectionContext, $a53f0f6636929daa$export$6e2c8f0811a474ce as Section, $a53f0f6636929daa$export$a164736487e3f0ae as DefaultCollectionRenderer, $a53f0f6636929daa$export$2dbbd341daed716d as renderAfterDropIndicators, $a53f0f6636929daa$export$4feb769f8ddf26c5 as CollectionRendererContext, $a53f0f6636929daa$export$90e00781bc59d8f9 as usePersistedKeys};
//# sourceMappingURL=Collection.js.map
