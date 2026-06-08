var $048d76b84370f141$exports = require("./utils.cjs");
var $f7b82bedbb70abac$exports = require("./Collection.cjs");
var $993df839da838aaa$exports = require("./Link.cjs");
var $lMF0i$reactariauseBreadcrumbs = require("react-aria/useBreadcrumbs");
var $lMF0i$reactariaCollection = require("react-aria/Collection");
var $lMF0i$reactariaCollectionBuilder = require("react-aria/CollectionBuilder");
var $lMF0i$reactariaprivatecollectionsBaseCollection = require("react-aria/private/collections/BaseCollection");
var $lMF0i$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $lMF0i$reactariamergeProps = require("react-aria/mergeProps");
var $lMF0i$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "BreadcrumbsContext", function () { return $c3765ed6efe4e06d$export$65596d3621b0a4a0; });
$parcel$export(module.exports, "Breadcrumbs", function () { return $c3765ed6efe4e06d$export$2dc68d50d56fbbd; });
$parcel$export(module.exports, "Breadcrumb", function () { return $c3765ed6efe4e06d$export$dabcc1ec9dd9d1cc; });
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









const $c3765ed6efe4e06d$export$65596d3621b0a4a0 = /*#__PURE__*/ (0, $lMF0i$react.createContext)(null);
const $c3765ed6efe4e06d$export$2dc68d50d56fbbd = /*#__PURE__*/ (0, $lMF0i$react.forwardRef)(function Breadcrumbs(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $c3765ed6efe4e06d$export$65596d3621b0a4a0);
    let { CollectionRoot: CollectionRoot } = (0, $lMF0i$react.useContext)((0, $f7b82bedbb70abac$exports.CollectionRendererContext));
    let { navProps: navProps } = (0, $lMF0i$reactariauseBreadcrumbs.useBreadcrumbs)(props);
    let DOMProps = (0, $lMF0i$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement((0, $lMF0i$reactariaCollectionBuilder.CollectionBuilder), {
        content: /*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement((0, $lMF0i$reactariaCollection.Collection), props)
    }, (collection)=>/*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement((0, $048d76b84370f141$exports.dom).ol, {
            render: props.render,
            ref: ref,
            ...(0, $lMF0i$reactariamergeProps.mergeProps)(DOMProps, navProps),
            slot: props.slot || undefined,
            style: props.style,
            className: props.className ?? 'react-aria-Breadcrumbs'
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement($c3765ed6efe4e06d$export$65596d3621b0a4a0.Provider, {
            value: props
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement(CollectionRoot, {
            collection: collection
        }))));
});
class $c3765ed6efe4e06d$var$BreadcrumbNode extends (0, $lMF0i$reactariaprivatecollectionsBaseCollection.CollectionNode) {
    static{
        this.type = 'item';
    }
}
const $c3765ed6efe4e06d$export$dabcc1ec9dd9d1cc = /*#__PURE__*/ (0, $lMF0i$reactariaCollectionBuilder.createLeafComponent)($c3765ed6efe4e06d$var$BreadcrumbNode, function Breadcrumb(props, ref, node) {
    // Recreating useBreadcrumbItem because we want to use composition instead of having the link builtin.
    let isCurrent = node.nextKey == null;
    let { isDisabled: isDisabled, onAction: onAction } = (0, $048d76b84370f141$exports.useSlottedContext)($c3765ed6efe4e06d$export$65596d3621b0a4a0);
    let linkProps = {
        'aria-current': isCurrent ? 'page' : null,
        isDisabled: isDisabled || isCurrent,
        onPress: ()=>onAction?.(node.key)
    };
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...node.props,
        children: node.rendered,
        values: {
            isDisabled: isDisabled || isCurrent,
            isCurrent: isCurrent
        },
        defaultClassName: 'react-aria-Breadcrumb'
    });
    let DOMProps = (0, $lMF0i$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement((0, $048d76b84370f141$exports.dom).li, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        "data-disabled": isDisabled || isCurrent || undefined,
        "data-current": isCurrent || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($lMF0i$react))).createElement((0, $993df839da838aaa$exports.LinkContext).Provider, {
        value: linkProps
    }, renderProps.children));
});


//# sourceMappingURL=Breadcrumbs.cjs.map
