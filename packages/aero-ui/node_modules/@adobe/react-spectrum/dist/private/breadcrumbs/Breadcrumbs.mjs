import {ActionButton as $b41412308e87d8d9$export$cfc7921d29ef7b80} from "../button/ActionButton.mjs";
import {BreadcrumbItem as $340e2b286c56680c$export$c13f210c706eb549} from "./BreadcrumbItem.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Menu as $8cccdb0b63bfcdeb$export$d9b273488cd8ce6f} from "../menu/Menu.mjs";
import {MenuTrigger as $9928637078ff3033$export$27d2ad3c5815583e} from "../menu/MenuTrigger.mjs";
import "../breadcrumb_vars.css";
import $cY0zE$breadcrumb_vars_cssmjs from "../breadcrumb_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useBreadcrumbs as $cY0zE$useBreadcrumbs} from "react-aria/useBreadcrumbs";
import $cY0zE$spectrumiconsuiFolderBreadcrumb from "@spectrum-icons/ui/FolderBreadcrumb";
import $cY0zE$react, {useRef as $cY0zE$useRef, useCallback as $cY0zE$useCallback} from "react";
import {useLayoutEffect as $cY0zE$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useResizeObserver as $cY0zE$useResizeObserver} from "react-aria/private/utils/useResizeObserver";
import {useValueEffect as $cY0zE$useValueEffect} from "react-aria/private/utils/useValueEffect";


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














const $aada1603ca29256c$var$MIN_VISIBLE_ITEMS = 1;
const $aada1603ca29256c$var$MAX_VISIBLE_ITEMS = 4;
const $aada1603ca29256c$export$2dc68d50d56fbbd = /*#__PURE__*/ (0, $cY0zE$react).forwardRef(function Breadcrumbs(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { size: size = 'L', isMultiline: isMultiline, children: children, showRoot: showRoot, isDisabled: isDisabled, onAction: onAction, autoFocusCurrent: autoFocusCurrent, ...otherProps } = props;
    // Not using React.Children.toArray because it mutates the key prop.
    let childArray = [];
    (0, $cY0zE$react).Children.forEach(children, (child, index)=>{
        if (/*#__PURE__*/ (0, $cY0zE$react).isValidElement(child)) {
            if (child.key == null) child = /*#__PURE__*/ (0, $cY0zE$react).cloneElement(child, {
                key: index
            });
            childArray.push(child);
        }
    });
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let listRef = (0, $cY0zE$useRef)(null);
    let [visibleItems, setVisibleItems] = (0, $cY0zE$useValueEffect)(childArray.length);
    let { navProps: navProps } = (0, $cY0zE$useBreadcrumbs)(props);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let updateOverflow = (0, $cY0zE$useCallback)(()=>{
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
            let maxVisibleItems = $aada1603ca29256c$var$MAX_VISIBLE_ITEMS;
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
            return Math.max($aada1603ca29256c$var$MIN_VISIBLE_ITEMS, Math.min(maxVisibleItems, newVisibleItems));
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
    (0, $cY0zE$useResizeObserver)({
        ref: domRef,
        onResize: updateOverflow
    });
    let lastChildren = (0, $cY0zE$useRef)(null);
    (0, $cY0zE$useLayoutEffect)(()=>{
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
        let menuItem = /*#__PURE__*/ (0, $cY0zE$react).createElement((0, $340e2b286c56680c$export$c13f210c706eb549), {
            key: "menu",
            isMenu: true
        }, /*#__PURE__*/ (0, $cY0zE$react).createElement((0, $9928637078ff3033$export$27d2ad3c5815583e), null, /*#__PURE__*/ (0, $cY0zE$react).createElement((0, $b41412308e87d8d9$export$cfc7921d29ef7b80), {
            UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cY0zE$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs-actionButton'),
            "aria-label": "\u2026",
            isQuiet: true,
            isDisabled: isDisabled
        }, /*#__PURE__*/ (0, $cY0zE$react).createElement((0, $cY0zE$spectrumiconsuiFolderBreadcrumb), null)), /*#__PURE__*/ (0, $cY0zE$react).createElement((0, $8cccdb0b63bfcdeb$export$d9b273488cd8ce6f), {
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
        return /*#__PURE__*/ (0, $cY0zE$react).createElement("li", {
            key: index,
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cY0zE$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs-item')
        }, /*#__PURE__*/ (0, $cY0zE$react).createElement((0, $340e2b286c56680c$export$c13f210c706eb549), {
            ...child.props,
            key: key,
            isCurrent: isCurrent,
            isDisabled: isDisabled,
            onPress: onPress,
            autoFocus: isCurrent && autoFocusCurrent
        }, child.props.children));
    });
    return /*#__PURE__*/ (0, $cY0zE$react).createElement("nav", {
        ...styleProps,
        ...navProps,
        ref: domRef
    }, /*#__PURE__*/ (0, $cY0zE$react).createElement("ul", {
        ref: listRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cY0zE$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs', {
            'spectrum-Breadcrumbs--small': size === 'S',
            'spectrum-Breadcrumbs--medium': size === 'M',
            'spectrum-Breadcrumbs--multiline': isMultiline,
            'spectrum-Breadcrumbs--showRoot': showRoot,
            'is-disabled': isDisabled
        }, styleProps.className)
    }, breadcrumbItems));
});


export {$aada1603ca29256c$export$2dc68d50d56fbbd as Breadcrumbs};
//# sourceMappingURL=Breadcrumbs.mjs.map
