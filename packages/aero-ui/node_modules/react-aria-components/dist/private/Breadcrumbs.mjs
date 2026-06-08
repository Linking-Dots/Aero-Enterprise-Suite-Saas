import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {CollectionRendererContext as $263ab7fc0f95ccdb$export$4feb769f8ddf26c5} from "./Collection.mjs";
import {LinkContext as $984a1fc08f87e4f3$export$e2509388b49734e7} from "./Link.mjs";
import {useBreadcrumbs as $hEiKI$useBreadcrumbs} from "react-aria/useBreadcrumbs";
import {Collection as $hEiKI$Collection} from "react-aria/Collection";
import {CollectionBuilder as $hEiKI$CollectionBuilder, createLeafComponent as $hEiKI$createLeafComponent} from "react-aria/CollectionBuilder";
import {CollectionNode as $hEiKI$CollectionNode} from "react-aria/private/collections/BaseCollection";
import {filterDOMProps as $hEiKI$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $hEiKI$mergeProps} from "react-aria/mergeProps";
import $hEiKI$react, {createContext as $hEiKI$createContext, forwardRef as $hEiKI$forwardRef, useContext as $hEiKI$useContext} from "react";

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









const $65dbe90f868fa5f4$export$65596d3621b0a4a0 = /*#__PURE__*/ (0, $hEiKI$createContext)(null);
const $65dbe90f868fa5f4$export$2dc68d50d56fbbd = /*#__PURE__*/ (0, $hEiKI$forwardRef)(function Breadcrumbs(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $65dbe90f868fa5f4$export$65596d3621b0a4a0);
    let { CollectionRoot: CollectionRoot } = (0, $hEiKI$useContext)((0, $263ab7fc0f95ccdb$export$4feb769f8ddf26c5));
    let { navProps: navProps } = (0, $hEiKI$useBreadcrumbs)(props);
    let DOMProps = (0, $hEiKI$filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    return /*#__PURE__*/ (0, $hEiKI$react).createElement((0, $hEiKI$CollectionBuilder), {
        content: /*#__PURE__*/ (0, $hEiKI$react).createElement((0, $hEiKI$Collection), props)
    }, (collection)=>/*#__PURE__*/ (0, $hEiKI$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).ol, {
            render: props.render,
            ref: ref,
            ...(0, $hEiKI$mergeProps)(DOMProps, navProps),
            slot: props.slot || undefined,
            style: props.style,
            className: props.className ?? 'react-aria-Breadcrumbs'
        }, /*#__PURE__*/ (0, $hEiKI$react).createElement($65dbe90f868fa5f4$export$65596d3621b0a4a0.Provider, {
            value: props
        }, /*#__PURE__*/ (0, $hEiKI$react).createElement(CollectionRoot, {
            collection: collection
        }))));
});
class $65dbe90f868fa5f4$var$BreadcrumbNode extends (0, $hEiKI$CollectionNode) {
    static{
        this.type = 'item';
    }
}
const $65dbe90f868fa5f4$export$dabcc1ec9dd9d1cc = /*#__PURE__*/ (0, $hEiKI$createLeafComponent)($65dbe90f868fa5f4$var$BreadcrumbNode, function Breadcrumb(props, ref, node) {
    // Recreating useBreadcrumbItem because we want to use composition instead of having the link builtin.
    let isCurrent = node.nextKey == null;
    let { isDisabled: isDisabled, onAction: onAction } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($65dbe90f868fa5f4$export$65596d3621b0a4a0);
    let linkProps = {
        'aria-current': isCurrent ? 'page' : null,
        isDisabled: isDisabled || isCurrent,
        onPress: ()=>onAction?.(node.key)
    };
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...node.props,
        children: node.rendered,
        values: {
            isDisabled: isDisabled || isCurrent,
            isCurrent: isCurrent
        },
        defaultClassName: 'react-aria-Breadcrumb'
    });
    let DOMProps = (0, $hEiKI$filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $hEiKI$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).li, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        "data-disabled": isDisabled || isCurrent || undefined,
        "data-current": isCurrent || undefined
    }, /*#__PURE__*/ (0, $hEiKI$react).createElement((0, $984a1fc08f87e4f3$export$e2509388b49734e7).Provider, {
        value: linkProps
    }, renderProps.children));
});


export {$65dbe90f868fa5f4$export$65596d3621b0a4a0 as BreadcrumbsContext, $65dbe90f868fa5f4$export$2dc68d50d56fbbd as Breadcrumbs, $65dbe90f868fa5f4$export$dabcc1ec9dd9d1cc as Breadcrumb};
//# sourceMappingURL=Breadcrumbs.mjs.map
