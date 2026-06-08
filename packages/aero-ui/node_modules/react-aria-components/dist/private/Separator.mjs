import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415} from "./utils.mjs";
import {useSeparator as $adn6H$useSeparator} from "react-aria/useSeparator";
import {CollectionNode as $adn6H$CollectionNode} from "react-aria/private/collections/BaseCollection";
import {createLeafComponent as $adn6H$createLeafComponent} from "react-aria/CollectionBuilder";
import {filterDOMProps as $adn6H$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $adn6H$mergeProps} from "react-aria/mergeProps";
import $adn6H$react, {createContext as $adn6H$createContext} from "react";

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






const $e28ab3efe3e87743$export$6615d83f6de245ce = /*#__PURE__*/ (0, $adn6H$createContext)({});
class $e28ab3efe3e87743$export$7750289ca694c0b5 extends (0, $adn6H$CollectionNode) {
    static{
        this.type = 'separator';
    }
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
const $e28ab3efe3e87743$export$1ff3c3f08ae963c0 = /*#__PURE__*/ (0, $adn6H$createLeafComponent)($e28ab3efe3e87743$export$7750289ca694c0b5, function Separator(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $e28ab3efe3e87743$export$6615d83f6de245ce);
    let { elementType: elementType, orientation: orientation, style: style, className: className, slot: slot, ...otherProps } = props;
    let Element = elementType || 'hr';
    if (Element === 'hr' && orientation === 'vertical') Element = 'div';
    let ElementType = (0, $7230ffa83bc0c2cf$export$df3a06d6289f983e)[Element];
    let { separatorProps: separatorProps } = (0, $adn6H$useSeparator)({
        ...otherProps,
        elementType: elementType,
        orientation: orientation
    });
    let DOMProps = (0, $adn6H$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $adn6H$react).createElement(ElementType, {
        render: props.render,
        ...(0, $adn6H$mergeProps)(DOMProps, separatorProps),
        style: style,
        className: className ?? 'react-aria-Separator',
        ref: ref,
        slot: slot || undefined
    });
});


export {$e28ab3efe3e87743$export$6615d83f6de245ce as SeparatorContext, $e28ab3efe3e87743$export$7750289ca694c0b5 as SeparatorNode, $e28ab3efe3e87743$export$1ff3c3f08ae963c0 as Separator};
//# sourceMappingURL=Separator.mjs.map
