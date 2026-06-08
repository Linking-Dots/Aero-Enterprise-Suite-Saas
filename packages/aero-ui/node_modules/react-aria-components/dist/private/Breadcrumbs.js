import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {CollectionRendererContext as $a53f0f6636929daa$export$4feb769f8ddf26c5} from "./Collection.js";
import {LinkContext as $73532136ed4d5f54$export$e2509388b49734e7} from "./Link.js";
import {useBreadcrumbs as $fwRQ4$useBreadcrumbs} from "react-aria/useBreadcrumbs";
import {Collection as $fwRQ4$Collection} from "react-aria/Collection";
import {CollectionBuilder as $fwRQ4$CollectionBuilder, createLeafComponent as $fwRQ4$createLeafComponent} from "react-aria/CollectionBuilder";
import {CollectionNode as $fwRQ4$CollectionNode} from "react-aria/private/collections/BaseCollection";
import {filterDOMProps as $fwRQ4$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $fwRQ4$mergeProps} from "react-aria/mergeProps";
import $fwRQ4$react, {createContext as $fwRQ4$createContext, forwardRef as $fwRQ4$forwardRef, useContext as $fwRQ4$useContext} from "react";

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









const $f9712338f630eb03$export$65596d3621b0a4a0 = /*#__PURE__*/ (0, $fwRQ4$createContext)(null);
const $f9712338f630eb03$export$2dc68d50d56fbbd = /*#__PURE__*/ (0, $fwRQ4$forwardRef)(function Breadcrumbs(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $f9712338f630eb03$export$65596d3621b0a4a0);
    let { CollectionRoot: CollectionRoot } = (0, $fwRQ4$useContext)((0, $a53f0f6636929daa$export$4feb769f8ddf26c5));
    let { navProps: navProps } = (0, $fwRQ4$useBreadcrumbs)(props);
    let DOMProps = (0, $fwRQ4$filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    return /*#__PURE__*/ (0, $fwRQ4$react).createElement((0, $fwRQ4$CollectionBuilder), {
        content: /*#__PURE__*/ (0, $fwRQ4$react).createElement((0, $fwRQ4$Collection), props)
    }, (collection)=>{
        var _props_className;
        return /*#__PURE__*/ (0, $fwRQ4$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).ol, {
            render: props.render,
            ref: ref,
            ...(0, $fwRQ4$mergeProps)(DOMProps, navProps),
            slot: props.slot || undefined,
            style: props.style,
            className: (_props_className = props.className) !== null && _props_className !== void 0 ? _props_className : 'react-aria-Breadcrumbs'
        }, /*#__PURE__*/ (0, $fwRQ4$react).createElement($f9712338f630eb03$export$65596d3621b0a4a0.Provider, {
            value: props
        }, /*#__PURE__*/ (0, $fwRQ4$react).createElement(CollectionRoot, {
            collection: collection
        })));
    });
});
class $f9712338f630eb03$var$BreadcrumbNode extends (0, $fwRQ4$CollectionNode) {
}
$f9712338f630eb03$var$BreadcrumbNode.type = 'item';
const $f9712338f630eb03$export$dabcc1ec9dd9d1cc = /*#__PURE__*/ (0, $fwRQ4$createLeafComponent)($f9712338f630eb03$var$BreadcrumbNode, function Breadcrumb(props, ref, node) {
    // Recreating useBreadcrumbItem because we want to use composition instead of having the link builtin.
    let isCurrent = node.nextKey == null;
    let { isDisabled: isDisabled, onAction: onAction } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)($f9712338f630eb03$export$65596d3621b0a4a0);
    let linkProps = {
        'aria-current': isCurrent ? 'page' : null,
        isDisabled: isDisabled || isCurrent,
        onPress: ()=>onAction === null || onAction === void 0 ? void 0 : onAction(node.key)
    };
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...node.props,
        children: node.rendered,
        values: {
            isDisabled: isDisabled || isCurrent,
            isCurrent: isCurrent
        },
        defaultClassName: 'react-aria-Breadcrumb'
    });
    let DOMProps = (0, $fwRQ4$filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $fwRQ4$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).li, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        "data-disabled": isDisabled || isCurrent || undefined,
        "data-current": isCurrent || undefined
    }, /*#__PURE__*/ (0, $fwRQ4$react).createElement((0, $73532136ed4d5f54$export$e2509388b49734e7).Provider, {
        value: linkProps
    }, renderProps.children));
});


export {$f9712338f630eb03$export$65596d3621b0a4a0 as BreadcrumbsContext, $f9712338f630eb03$export$2dc68d50d56fbbd as Breadcrumbs, $f9712338f630eb03$export$dabcc1ec9dd9d1cc as Breadcrumb};
//# sourceMappingURL=Breadcrumbs.js.map
