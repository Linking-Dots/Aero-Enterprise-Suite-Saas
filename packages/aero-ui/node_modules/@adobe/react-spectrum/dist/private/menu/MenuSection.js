import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {MenuItem as $53bbb287499fadf8$export$2ce376c2cc3355c8} from "./MenuItem.js";
import "../menu_vars.css";
import $agEYc$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {getChildNodes as $agEYc$getChildNodes} from "react-stately/private/collections/getChildNodes";
import $agEYc$react, {Fragment as $agEYc$Fragment} from "react";
import {useMenuSection as $agEYc$useMenuSection} from "react-aria/useMenu";
import {useSeparator as $agEYc$useSeparator} from "react-aria/useSeparator";


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






function $0dc60032e766c118$export$4b1545b4f2016d26(props) {
    var _filter_at;
    let { item: item, state: state } = props;
    let { itemProps: itemProps, headingProps: headingProps, groupProps: groupProps } = (0, $agEYc$useMenuSection)({
        heading: item.rendered,
        'aria-label': item['aria-label']
    });
    let { separatorProps: separatorProps } = (0, $agEYc$useSeparator)({
        elementType: 'div'
    });
    let firstSectionKey = state.collection.getFirstKey();
    let lastSectionKey = (_filter_at = [
        ...state.collection
    ].filter((node)=>node.type === 'section').at(-1)) === null || _filter_at === void 0 ? void 0 : _filter_at.key;
    let sectionIsFirst = firstSectionKey === item.key && state.collection.getFirstKey() === firstSectionKey;
    let lastKey = state.collection.getLastKey();
    let sectionIsLast = lastSectionKey === item.key && lastKey != null && state.collection.getItem(lastKey).parentKey === lastSectionKey;
    return /*#__PURE__*/ (0, $agEYc$react).createElement((0, $agEYc$Fragment), null, item.key !== state.collection.getFirstKey() && /*#__PURE__*/ (0, $agEYc$react).createElement("div", {
        ...separatorProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($agEYc$menu_vars_cssmjs))), 'spectrum-Menu-divider')
    }), /*#__PURE__*/ (0, $agEYc$react).createElement("div", itemProps, item.rendered && /*#__PURE__*/ (0, $agEYc$react).createElement("span", {
        ...headingProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($agEYc$menu_vars_cssmjs))), 'spectrum-Menu-sectionHeading')
    }, item.rendered), /*#__PURE__*/ (0, $agEYc$react).createElement("div", {
        ...groupProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($agEYc$menu_vars_cssmjs))), 'spectrum-Menu', {
            'spectrum-Menu-section--noHeading': item.rendered == null,
            'spectrum-Menu-section--isFirst': sectionIsFirst,
            'spectrum-Menu-section--isLast': sectionIsLast
        })
    }, [
        ...(0, $agEYc$getChildNodes)(item, state.collection)
    ].map((node)=>{
        let item = /*#__PURE__*/ (0, $agEYc$react).createElement((0, $53bbb287499fadf8$export$2ce376c2cc3355c8), {
            key: node.key,
            item: node,
            state: state
        });
        if (node.wrapper) item = node.wrapper(item);
        return item;
    }))));
}


export {$0dc60032e766c118$export$4b1545b4f2016d26 as MenuSection};
//# sourceMappingURL=MenuSection.js.map
