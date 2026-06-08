import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import {useSeparator as $6vd2a$useSeparator} from "react-aria/useSeparator";
import {CollectionNode as $6vd2a$CollectionNode} from "react-aria/private/collections/BaseCollection";
import {createLeafComponent as $6vd2a$createLeafComponent} from "react-aria/CollectionBuilder";
import {filterDOMProps as $6vd2a$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $6vd2a$mergeProps} from "react-aria/mergeProps";
import $6vd2a$react, {createContext as $6vd2a$createContext} from "react";

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






const $469f9b725520971a$export$6615d83f6de245ce = /*#__PURE__*/ (0, $6vd2a$createContext)({});
class $469f9b725520971a$export$7750289ca694c0b5 extends (0, $6vd2a$CollectionNode) {
    filter(collection, newCollection) {
        let prevItem = newCollection.getItem(this.prevKey);
        if (prevItem && prevItem.type !== 'separator') {
            let clone = this.clone();
            newCollection.addDescendants(clone, collection);
            return clone;
        }
        return null;
    }
}
$469f9b725520971a$export$7750289ca694c0b5.type = 'separator';
const $469f9b725520971a$export$1ff3c3f08ae963c0 = /*#__PURE__*/ (0, $6vd2a$createLeafComponent)($469f9b725520971a$export$7750289ca694c0b5, function Separator(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $469f9b725520971a$export$6615d83f6de245ce);
    let { elementType: elementType, orientation: orientation, style: style, className: className, slot: slot, ...otherProps } = props;
    let Element = elementType || 'hr';
    if (Element === 'hr' && orientation === 'vertical') Element = 'div';
    let ElementType = (0, $b7b7a92703138c9b$export$df3a06d6289f983e)[Element];
    let { separatorProps: separatorProps } = (0, $6vd2a$useSeparator)({
        ...otherProps,
        elementType: elementType,
        orientation: orientation
    });
    let DOMProps = (0, $6vd2a$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $6vd2a$react).createElement(ElementType, {
        render: props.render,
        ...(0, $6vd2a$mergeProps)(DOMProps, separatorProps),
        style: style,
        className: className !== null && className !== void 0 ? className : 'react-aria-Separator',
        ref: ref,
        slot: slot || undefined
    });
});


export {$469f9b725520971a$export$6615d83f6de245ce as SeparatorContext, $469f9b725520971a$export$7750289ca694c0b5 as SeparatorNode, $469f9b725520971a$export$1ff3c3f08ae963c0 as Separator};
//# sourceMappingURL=Separator.js.map
