import {ActionButton as $c265dbb41bfd0210$export$cfc7921d29ef7b80} from "../button/ActionButton.js";
import {BreadcrumbItem as $737db8b958dde4c0$export$c13f210c706eb549} from "./BreadcrumbItem.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Menu as $79ddee63a726ea3d$export$d9b273488cd8ce6f} from "../menu/Menu.js";
import {MenuTrigger as $9f6ebde23392f425$export$27d2ad3c5815583e} from "../menu/MenuTrigger.js";
import "../breadcrumb_vars.css";
import $ilQlF$breadcrumb_vars_cssmjs from "../breadcrumb_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useBreadcrumbs as $ilQlF$useBreadcrumbs} from "react-aria/useBreadcrumbs";
import $ilQlF$spectrumiconsuiFolderBreadcrumb from "@spectrum-icons/ui/FolderBreadcrumb";
import $ilQlF$react, {useRef as $ilQlF$useRef, useCallback as $ilQlF$useCallback} from "react";
import {useLayoutEffect as $ilQlF$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useResizeObserver as $ilQlF$useResizeObserver} from "react-aria/private/utils/useResizeObserver";
import {useValueEffect as $ilQlF$useValueEffect} from "react-aria/private/utils/useValueEffect";


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














const $5fa86543125c475c$var$MIN_VISIBLE_ITEMS = 1;
const $5fa86543125c475c$var$MAX_VISIBLE_ITEMS = 4;
const $5fa86543125c475c$export$2dc68d50d56fbbd = /*#__PURE__*/ (0, $ilQlF$react).forwardRef(function Breadcrumbs(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { size: size = 'L', isMultiline: isMultiline, children: children, showRoot: showRoot, isDisabled: isDisabled, onAction: onAction, autoFocusCurrent: autoFocusCurrent, ...otherProps } = props;
    // Not using React.Children.toArray because it mutates the key prop.
    let childArray = [];
    (0, $ilQlF$react).Children.forEach(children, (child, index)=>{
        if (/*#__PURE__*/ (0, $ilQlF$react).isValidElement(child)) {
            if (child.key == null) child = /*#__PURE__*/ (0, $ilQlF$react).cloneElement(child, {
                key: index
            });
            childArray.push(child);
        }
    });
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let listRef = (0, $ilQlF$useRef)(null);
    let [visibleItems, setVisibleItems] = (0, $ilQlF$useValueEffect)(childArray.length);
    let { navProps: navProps } = (0, $ilQlF$useBreadcrumbs)(props);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let updateOverflow = (0, $ilQlF$useCallback)(()=>{
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
            let maxVisibleItems = $5fa86543125c475c$var$MAX_VISIBLE_ITEMS;
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
            return Math.max($5fa86543125c475c$var$MIN_VISIBLE_ITEMS, Math.min(maxVisibleItems, newVisibleItems));
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
    (0, $ilQlF$useResizeObserver)({
        ref: domRef,
        onResize: updateOverflow
    });
    let lastChildren = (0, $ilQlF$useRef)(null);
    (0, $ilQlF$useLayoutEffect)(()=>{
        if (children !== lastChildren.current) {
            lastChildren.current = children;
            updateOverflow();
        }
    });
    let contents = childArray;
    if (childArray.length > visibleItems) {
        let selectedItem = childArray[childArray.length - 1];
        var _selectedItem_key;
        let selectedKey = (_selectedItem_key = selectedItem.key) !== null && _selectedItem_key !== void 0 ? _selectedItem_key : childArray.length - 1;
        let onMenuAction = (key)=>{
            // Don't fire onAction when clicking on the last item
            if (key !== selectedKey && onAction) onAction(key);
        };
        let menuItem = /*#__PURE__*/ (0, $ilQlF$react).createElement((0, $737db8b958dde4c0$export$c13f210c706eb549), {
            key: "menu",
            isMenu: true
        }, /*#__PURE__*/ (0, $ilQlF$react).createElement((0, $9f6ebde23392f425$export$27d2ad3c5815583e), null, /*#__PURE__*/ (0, $ilQlF$react).createElement((0, $c265dbb41bfd0210$export$cfc7921d29ef7b80), {
            UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ilQlF$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs-actionButton'),
            "aria-label": "\u2026",
            isQuiet: true,
            isDisabled: isDisabled
        }, /*#__PURE__*/ (0, $ilQlF$react).createElement((0, $ilQlF$spectrumiconsuiFolderBreadcrumb), null)), /*#__PURE__*/ (0, $ilQlF$react).createElement((0, $79ddee63a726ea3d$export$d9b273488cd8ce6f), {
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
        var _child_key;
        let key = (_child_key = child.key) !== null && _child_key !== void 0 ? _child_key : index;
        let onPress = ()=>{
            if (onAction) onAction(key);
        };
        return /*#__PURE__*/ (0, $ilQlF$react).createElement("li", {
            key: index,
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ilQlF$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs-item')
        }, /*#__PURE__*/ (0, $ilQlF$react).createElement((0, $737db8b958dde4c0$export$c13f210c706eb549), {
            ...child.props,
            key: key,
            isCurrent: isCurrent,
            isDisabled: isDisabled,
            onPress: onPress,
            autoFocus: isCurrent && autoFocusCurrent
        }, child.props.children));
    });
    return /*#__PURE__*/ (0, $ilQlF$react).createElement("nav", {
        ...styleProps,
        ...navProps,
        ref: domRef
    }, /*#__PURE__*/ (0, $ilQlF$react).createElement("ul", {
        ref: listRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ilQlF$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs', {
            'spectrum-Breadcrumbs--small': size === 'S',
            'spectrum-Breadcrumbs--medium': size === 'M',
            'spectrum-Breadcrumbs--multiline': isMultiline,
            'spectrum-Breadcrumbs--showRoot': showRoot,
            'is-disabled': isDisabled
        }, styleProps.className)
    }, breadcrumbItems));
});


export {$5fa86543125c475c$export$2dc68d50d56fbbd as Breadcrumbs};
//# sourceMappingURL=Breadcrumbs.js.map
