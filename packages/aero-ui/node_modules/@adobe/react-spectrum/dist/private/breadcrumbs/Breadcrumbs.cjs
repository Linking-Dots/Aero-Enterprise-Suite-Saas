var $183c5173677598aa$exports = require("../button/ActionButton.cjs");
var $113504779a40c9d6$exports = require("./BreadcrumbItem.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $802fb5441f76e7b0$exports = require("../menu/Menu.cjs");
var $98227f5fd590c993$exports = require("../menu/MenuTrigger.cjs");
require("../breadcrumb_vars.css");
var $c876acff1ed460c9$exports = require("../breadcrumb_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $6jDQm$reactariauseBreadcrumbs = require("react-aria/useBreadcrumbs");
var $6jDQm$spectrumiconsuiFolderBreadcrumb = require("@spectrum-icons/ui/FolderBreadcrumb");
var $6jDQm$react = require("react");
var $6jDQm$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $6jDQm$reactariaprivateutilsuseResizeObserver = require("react-aria/private/utils/useResizeObserver");
var $6jDQm$reactariaprivateutilsuseValueEffect = require("react-aria/private/utils/useValueEffect");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Breadcrumbs", function () { return $082087d8676cf0c8$export$2dc68d50d56fbbd; });
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














const $082087d8676cf0c8$var$MIN_VISIBLE_ITEMS = 1;
const $082087d8676cf0c8$var$MAX_VISIBLE_ITEMS = 4;
const $082087d8676cf0c8$export$2dc68d50d56fbbd = /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).forwardRef(function Breadcrumbs(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { size: size = 'L', isMultiline: isMultiline, children: children, showRoot: showRoot, isDisabled: isDisabled, onAction: onAction, autoFocusCurrent: autoFocusCurrent, ...otherProps } = props;
    // Not using React.Children.toArray because it mutates the key prop.
    let childArray = [];
    (0, ($parcel$interopDefault($6jDQm$react))).Children.forEach(children, (child, index)=>{
        if (/*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).isValidElement(child)) {
            if (child.key == null) child = /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).cloneElement(child, {
                key: index
            });
            childArray.push(child);
        }
    });
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let listRef = (0, $6jDQm$react.useRef)(null);
    let [visibleItems, setVisibleItems] = (0, $6jDQm$reactariaprivateutilsuseValueEffect.useValueEffect)(childArray.length);
    let { navProps: navProps } = (0, $6jDQm$reactariauseBreadcrumbs.useBreadcrumbs)(props);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let updateOverflow = (0, $6jDQm$react.useCallback)(()=>{
        let computeVisibleItems = (visibleItems)=>{
            // Refs can be null at runtime.
            let currListRef = listRef.current;
            if (!currListRef) return visibleItems;
            let listItems = Array.from(currListRef.children);
            if (listItems.length <= 0) return visibleItems;
            let containerWidth = currListRef.offsetWidth;
            let isShowingMenu = childArray.length > visibleItems;
            let calculatedWidth = 0;
            let newVisibleItems = 0;
            let maxVisibleItems = $082087d8676cf0c8$var$MAX_VISIBLE_ITEMS;
            if (showRoot) {
                calculatedWidth += listItems.shift().offsetWidth;
                newVisibleItems++;
            }
            if (isShowingMenu) {
                calculatedWidth += listItems.shift().offsetWidth;
                maxVisibleItems--;
            }
            if (showRoot && calculatedWidth >= containerWidth) newVisibleItems--;
            // TODO: what if multiline and only one breadcrumb??
            if (isMultiline) {
                listItems.pop();
                newVisibleItems++;
            } else if (listItems.length > 0) {
                // Ensure the last breadcrumb isn't truncated when we measure it.
                let last = listItems.pop();
                last.style.overflow = 'visible';
                calculatedWidth += last.offsetWidth;
                if (calculatedWidth < containerWidth) newVisibleItems++;
                last.style.overflow = '';
            }
            for (let breadcrumb of listItems.reverse()){
                calculatedWidth += breadcrumb.offsetWidth;
                if (calculatedWidth < containerWidth) newVisibleItems++;
            }
            return Math.max($082087d8676cf0c8$var$MIN_VISIBLE_ITEMS, Math.min(maxVisibleItems, newVisibleItems));
        };
        setVisibleItems(function*() {
            // Update to show all items.
            yield childArray.length;
            // Measure, and update to show the items that fit.
            let newVisibleItems = computeVisibleItems(childArray.length);
            yield newVisibleItems;
            // If the number of items is less than the number of children,
            // then update again to ensure that the menu fits.
            if (newVisibleItems < childArray.length && newVisibleItems > 1) yield computeVisibleItems(newVisibleItems);
        });
    }, [
        childArray.length,
        setVisibleItems,
        showRoot,
        isMultiline
    ]);
    (0, $6jDQm$reactariaprivateutilsuseResizeObserver.useResizeObserver)({
        ref: domRef,
        onResize: updateOverflow
    });
    let lastChildren = (0, $6jDQm$react.useRef)(null);
    (0, $6jDQm$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        if (children !== lastChildren.current) {
            lastChildren.current = children;
            updateOverflow();
        }
    });
    let contents = childArray;
    if (childArray.length > visibleItems) {
        let selectedItem = childArray[childArray.length - 1];
        let selectedKey = selectedItem.key ?? childArray.length - 1;
        let onMenuAction = (key)=>{
            // Don't fire onAction when clicking on the last item
            if (key !== selectedKey && onAction) onAction(key);
        };
        let menuItem = /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement((0, $113504779a40c9d6$exports.BreadcrumbItem), {
            key: "menu",
            isMenu: true
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement((0, $98227f5fd590c993$exports.MenuTrigger), null, /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement((0, $183c5173677598aa$exports.ActionButton), {
            UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c876acff1ed460c9$exports))), 'spectrum-Breadcrumbs-actionButton'),
            "aria-label": "\u2026",
            isQuiet: true,
            isDisabled: isDisabled
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement((0, ($parcel$interopDefault($6jDQm$spectrumiconsuiFolderBreadcrumb))), null)), /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement((0, $802fb5441f76e7b0$exports.Menu), {
            selectionMode: "single",
            selectedKeys: [
                selectedKey
            ],
            onAction: onMenuAction
        }, childArray)));
        contents = [
            menuItem
        ];
        let breadcrumbs = [
            ...childArray
        ];
        let endItems = visibleItems;
        if (showRoot && visibleItems > 1) {
            let rootItem = breadcrumbs.shift();
            if (rootItem) contents.unshift(rootItem);
            endItems--;
        }
        contents.push(...breadcrumbs.slice(-endItems));
    }
    let lastIndex = contents.length - 1;
    let breadcrumbItems = contents.map((child, index)=>{
        let isCurrent = index === lastIndex;
        let key = child.key ?? index;
        let onPress = ()=>{
            if (onAction) onAction(key);
        };
        return /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement("li", {
            key: index,
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c876acff1ed460c9$exports))), 'spectrum-Breadcrumbs-item')
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement((0, $113504779a40c9d6$exports.BreadcrumbItem), {
            ...child.props,
            key: key,
            isCurrent: isCurrent,
            isDisabled: isDisabled,
            onPress: onPress,
            autoFocus: isCurrent && autoFocusCurrent
        }, child.props.children));
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement("nav", {
        ...styleProps,
        ...navProps,
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($6jDQm$react))).createElement("ul", {
        ref: listRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c876acff1ed460c9$exports))), 'spectrum-Breadcrumbs', {
            'spectrum-Breadcrumbs--small': size === 'S',
            'spectrum-Breadcrumbs--medium': size === 'M',
            'spectrum-Breadcrumbs--multiline': isMultiline,
            'spectrum-Breadcrumbs--showRoot': showRoot,
            'is-disabled': isDisabled
        }, styleProps.className)
    }, breadcrumbItems));
});


//# sourceMappingURL=Breadcrumbs.cjs.map
