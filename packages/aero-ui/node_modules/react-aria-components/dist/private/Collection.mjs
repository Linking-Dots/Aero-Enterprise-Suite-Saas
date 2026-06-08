import {createBranchComponent as $f0m83$createBranchComponent} from "react-aria/CollectionBuilder";
import $f0m83$react, {createContext as $f0m83$createContext, useContext as $f0m83$useContext, isValidElement as $f0m83$isValidElement, cloneElement as $f0m83$cloneElement, useMemo as $f0m83$useMemo} from "react";
import {useCachedChildren as $f0m83$useCachedChildren} from "react-aria/private/collections/useCachedChildren";

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


const $263ab7fc0f95ccdb$export$d40e14dec8b060a8 = /*#__PURE__*/ (0, $f0m83$createContext)(null);
const $263ab7fc0f95ccdb$export$6e2c8f0811a474ce = /*#__PURE__*/ (0, $f0m83$createBranchComponent)('section', (props, ref, section)=>{
    let { name: name, render: render } = (0, $f0m83$useContext)($263ab7fc0f95ccdb$export$d40e14dec8b060a8);
    if (process.env.NODE_ENV !== 'production') console.warn(`<Section> is deprecated. Please use <${name}> instead.`);
    return render(props, ref, section, 'react-aria-Section');
});
const $263ab7fc0f95ccdb$export$a164736487e3f0ae = {
    CollectionRoot ({ collection: collection, renderDropIndicator: renderDropIndicator }) {
        return $263ab7fc0f95ccdb$var$useCollectionRender(collection, null, renderDropIndicator);
    },
    CollectionBranch ({ collection: collection, parent: parent, renderDropIndicator: renderDropIndicator }) {
        return $263ab7fc0f95ccdb$var$useCollectionRender(collection, parent, renderDropIndicator);
    }
};
function $263ab7fc0f95ccdb$var$useCollectionRender(collection, parent, renderDropIndicator) {
    return (0, $f0m83$useCachedChildren)({
        items: parent ? collection.getChildren(parent.key) : collection,
        dependencies: [
            renderDropIndicator
        ],
        children (node) {
            // Return a empty fragment since we don't want to render the content twice
            // If we don't skip the content node here, we end up rendering them twice in a Tree since we also render the content node in TreeItem
            if (node.type === 'content') return /*#__PURE__*/ (0, $f0m83$react).createElement((0, $f0m83$react).Fragment, null);
            let rendered = node.render(node);
            if (!renderDropIndicator || node.type !== 'item') return rendered;
            return /*#__PURE__*/ (0, $f0m83$react).createElement((0, $f0m83$react).Fragment, null, renderDropIndicator({
                type: 'item',
                key: node.key,
                dropPosition: 'before'
            }), rendered, $263ab7fc0f95ccdb$export$2dbbd341daed716d(collection, node, renderDropIndicator));
        }
    });
}
function $263ab7fc0f95ccdb$export$2dbbd341daed716d(collection, node, renderDropIndicator) {
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
        while(current?.type === 'item' && (!nextItemInFlattenedCollection || current.parentKey !== nextItemInFlattenedCollection.parentKey && nextItemInFlattenedCollection.level < current.level)){
            let indicator = renderDropIndicator({
                type: 'item',
                key: current.key,
                dropPosition: 'after'
            });
            if (/*#__PURE__*/ (0, $f0m83$isValidElement)(indicator)) afterIndicators.push(/*#__PURE__*/ (0, $f0m83$cloneElement)(indicator, {
                key: `${current.key}-after`
            }));
            current = current.parentKey != null ? collection.getItem(current.parentKey) : null;
        }
    }
    return afterIndicators;
}
const $263ab7fc0f95ccdb$export$4feb769f8ddf26c5 = /*#__PURE__*/ (0, $f0m83$createContext)($263ab7fc0f95ccdb$export$a164736487e3f0ae);
function $263ab7fc0f95ccdb$export$90e00781bc59d8f9(focusedKey) {
    return (0, $f0m83$useMemo)(()=>focusedKey != null ? new Set([
            focusedKey
        ]) : null, [
        focusedKey
    ]);
}


export {$263ab7fc0f95ccdb$export$d40e14dec8b060a8 as SectionContext, $263ab7fc0f95ccdb$export$6e2c8f0811a474ce as Section, $263ab7fc0f95ccdb$export$a164736487e3f0ae as DefaultCollectionRenderer, $263ab7fc0f95ccdb$export$2dbbd341daed716d as renderAfterDropIndicators, $263ab7fc0f95ccdb$export$4feb769f8ddf26c5 as CollectionRendererContext, $263ab7fc0f95ccdb$export$90e00781bc59d8f9 as usePersistedKeys};
//# sourceMappingURL=Collection.mjs.map
