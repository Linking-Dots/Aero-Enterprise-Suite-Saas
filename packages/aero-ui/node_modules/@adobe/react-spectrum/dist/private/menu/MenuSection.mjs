import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {MenuItem as $764fca59ff9a0c0a$export$2ce376c2cc3355c8} from "./MenuItem.mjs";
import "../menu_vars.css";
import $l6FQA$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {getChildNodes as $l6FQA$getChildNodes} from "react-stately/private/collections/getChildNodes";
import $l6FQA$react, {Fragment as $l6FQA$Fragment} from "react";
import {useMenuSection as $l6FQA$useMenuSection} from "react-aria/useMenu";
import {useSeparator as $l6FQA$useSeparator} from "react-aria/useSeparator";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2020 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 






function $8bf3363a4d12b22b$export$4b1545b4f2016d26(props) {
    let { item: item, state: state } = props;
    let { itemProps: itemProps, headingProps: headingProps, groupProps: groupProps } = (0, $l6FQA$useMenuSection)({
        heading: item.rendered,
        'aria-label': item['aria-label']
    });
    let { separatorProps: separatorProps } = (0, $l6FQA$useSeparator)({
        elementType: 'div'
    });
    let firstSectionKey = state.collection.getFirstKey();
    let lastSectionKey = [
        ...state.collection
    ].filter((node)=>node.type === 'section').at(-1)?.key;
    let sectionIsFirst = firstSectionKey === item.key && state.collection.getFirstKey() === firstSectionKey;
    let lastKey = state.collection.getLastKey();
    let sectionIsLast = lastSectionKey === item.key && lastKey != null && state.collection.getItem(lastKey).parentKey === lastSectionKey;
    return /*#__PURE__*/ (0, $l6FQA$react).createElement((0, $l6FQA$Fragment), null, item.key !== state.collection.getFirstKey() && /*#__PURE__*/ (0, $l6FQA$react).createElement("div", {
        ...separatorProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($l6FQA$menu_vars_cssmjs))), 'spectrum-Menu-divider')
    }), /*#__PURE__*/ (0, $l6FQA$react).createElement("div", itemProps, item.rendered && /*#__PURE__*/ (0, $l6FQA$react).createElement("span", {
        ...headingProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($l6FQA$menu_vars_cssmjs))), 'spectrum-Menu-sectionHeading')
    }, item.rendered), /*#__PURE__*/ (0, $l6FQA$react).createElement("div", {
        ...groupProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($l6FQA$menu_vars_cssmjs))), 'spectrum-Menu', {
            'spectrum-Menu-section--noHeading': item.rendered == null,
            'spectrum-Menu-section--isFirst': sectionIsFirst,
            'spectrum-Menu-section--isLast': sectionIsLast
        })
    }, [
        ...(0, $l6FQA$getChildNodes)(item, state.collection)
    ].map((node)=>{
        let item = /*#__PURE__*/ (0, $l6FQA$react).createElement((0, $764fca59ff9a0c0a$export$2ce376c2cc3355c8), {
            key: node.key,
            item: node,
            state: state
        });
        if (node.wrapper) item = node.wrapper(item);
        return item;
    }))));
}


export {$8bf3363a4d12b22b$export$4b1545b4f2016d26 as MenuSection};
//# sourceMappingURL=MenuSection.mjs.map
