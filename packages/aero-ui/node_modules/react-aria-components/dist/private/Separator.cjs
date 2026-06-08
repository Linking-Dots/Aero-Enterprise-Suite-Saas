var $048d76b84370f141$exports = require("./utils.cjs");
var $5zjXU$reactariauseSeparator = require("react-aria/useSeparator");
var $5zjXU$reactariaprivatecollectionsBaseCollection = require("react-aria/private/collections/BaseCollection");
var $5zjXU$reactariaCollectionBuilder = require("react-aria/CollectionBuilder");
var $5zjXU$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $5zjXU$reactariamergeProps = require("react-aria/mergeProps");
var $5zjXU$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SeparatorContext", function () { return $5a1b0036f8cbf051$export$6615d83f6de245ce; });
$parcel$export(module.exports, "Separator", function () { return $5a1b0036f8cbf051$export$1ff3c3f08ae963c0; });
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






const $5a1b0036f8cbf051$export$6615d83f6de245ce = /*#__PURE__*/ (0, $5zjXU$react.createContext)({});
class $5a1b0036f8cbf051$export$7750289ca694c0b5 extends (0, $5zjXU$reactariaprivatecollectionsBaseCollection.CollectionNode) {
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
const $5a1b0036f8cbf051$export$1ff3c3f08ae963c0 = /*#__PURE__*/ (0, $5zjXU$reactariaCollectionBuilder.createLeafComponent)($5a1b0036f8cbf051$export$7750289ca694c0b5, function Separator(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $5a1b0036f8cbf051$export$6615d83f6de245ce);
    let { elementType: elementType, orientation: orientation, style: style, className: className, slot: slot, ...otherProps } = props;
    let Element = elementType || 'hr';
    if (Element === 'hr' && orientation === 'vertical') Element = 'div';
    let ElementType = (0, $048d76b84370f141$exports.dom)[Element];
    let { separatorProps: separatorProps } = (0, $5zjXU$reactariauseSeparator.useSeparator)({
        ...otherProps,
        elementType: elementType,
        orientation: orientation
    });
    let DOMProps = (0, $5zjXU$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5zjXU$react))).createElement(ElementType, {
        render: props.render,
        ...(0, $5zjXU$reactariamergeProps.mergeProps)(DOMProps, separatorProps),
        style: style,
        className: className ?? 'react-aria-Separator',
        ref: ref,
        slot: slot || undefined
    });
});


//# sourceMappingURL=Separator.cjs.map
