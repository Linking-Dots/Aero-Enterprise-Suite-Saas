var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $f98c72ac58c30ee0$exports = require("./MenuItem.cjs");
require("../menu_vars.css");
var $35d34152ff885d5c$exports = require("../menu_vars_css.cjs");
var $bEG0U$reactstatelyprivatecollectionsgetChildNodes = require("react-stately/private/collections/getChildNodes");
var $bEG0U$react = require("react");
var $bEG0U$reactariauseMenu = require("react-aria/useMenu");
var $bEG0U$reactariauseSeparator = require("react-aria/useSeparator");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "MenuSection", function () { return $58c7a0147e48c32f$export$4b1545b4f2016d26; });
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






function $58c7a0147e48c32f$export$4b1545b4f2016d26(props) {
    let { item: item, state: state } = props;
    let { itemProps: itemProps, headingProps: headingProps, groupProps: groupProps } = (0, $bEG0U$reactariauseMenu.useMenuSection)({
        heading: item.rendered,
        'aria-label': item['aria-label']
    });
    let { separatorProps: separatorProps } = (0, $bEG0U$reactariauseSeparator.useSeparator)({
        elementType: 'div'
    });
    let firstSectionKey = state.collection.getFirstKey();
    let lastSectionKey = [
        ...state.collection
    ].filter((node)=>node.type === 'section').at(-1)?.key;
    let sectionIsFirst = firstSectionKey === item.key && state.collection.getFirstKey() === firstSectionKey;
    let lastKey = state.collection.getLastKey();
    let sectionIsLast = lastSectionKey === item.key && lastKey != null && state.collection.getItem(lastKey).parentKey === lastSectionKey;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($bEG0U$react))).createElement((0, $bEG0U$react.Fragment), null, item.key !== state.collection.getFirstKey() && /*#__PURE__*/ (0, ($parcel$interopDefault($bEG0U$react))).createElement("div", {
        ...separatorProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-divider')
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($bEG0U$react))).createElement("div", itemProps, item.rendered && /*#__PURE__*/ (0, ($parcel$interopDefault($bEG0U$react))).createElement("span", {
        ...headingProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-sectionHeading')
    }, item.rendered), /*#__PURE__*/ (0, ($parcel$interopDefault($bEG0U$react))).createElement("div", {
        ...groupProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu', {
            'spectrum-Menu-section--noHeading': item.rendered == null,
            'spectrum-Menu-section--isFirst': sectionIsFirst,
            'spectrum-Menu-section--isLast': sectionIsLast
        })
    }, [
        ...(0, $bEG0U$reactstatelyprivatecollectionsgetChildNodes.getChildNodes)(item, state.collection)
    ].map((node)=>{
        let item = /*#__PURE__*/ (0, ($parcel$interopDefault($bEG0U$react))).createElement((0, $f98c72ac58c30ee0$exports.MenuItem), {
            key: node.key,
            item: node,
            state: state
        });
        if (node.wrapper) item = node.wrapper(item);
        return item;
    }))));
}


//# sourceMappingURL=MenuSection.cjs.map
