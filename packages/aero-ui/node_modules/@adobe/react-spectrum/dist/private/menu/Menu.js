import {ActionButton as $c265dbb41bfd0210$export$cfc7921d29ef7b80} from "../button/ActionButton.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import $aDee0$intlStringsjs from "./intlStrings.js";
import {MenuContext as $a4d1910f0ff9d033$export$c7e742effb1c51e2, MenuStateContext as $a4d1910f0ff9d033$export$24aad8519b95b41b, useMenuStateContext as $a4d1910f0ff9d033$export$efa3856fc0e85e7f} from "./context.js";
import {MenuItem as $53bbb287499fadf8$export$2ce376c2cc3355c8} from "./MenuItem.js";
import {MenuSection as $0dc60032e766c118$export$4b1545b4f2016d26} from "./MenuSection.js";
import "../menu_vars.css";
import $aDee0$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useIsMobileDevice as $196ab9279fe71c29$export$736bf165441b18c7} from "../utils/useIsMobileDevice.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useMenu as $aDee0$useMenu} from "react-aria/useMenu";
import $aDee0$spectrumiconsuiArrowDownSmall from "@spectrum-icons/ui/ArrowDownSmall";
import {FocusScope as $aDee0$FocusScope} from "react-aria/FocusScope";
import {mergeProps as $aDee0$mergeProps} from "react-aria/mergeProps";
import $aDee0$react, {useContext as $aDee0$useContext, useState as $aDee0$useState, useRef as $aDee0$useRef, useEffect as $aDee0$useEffect} from "react";
import {useTreeState as $aDee0$useTreeState} from "react-stately/useTreeState";
import {useLayoutEffect as $aDee0$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useLocale as $aDee0$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $aDee0$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useSlotId as $aDee0$useSlotId} from "react-aria/private/utils/useId";
import {useSyncRef as $aDee0$useSyncRef} from "react-aria/private/utils/useSyncRef";


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




















const $79ddee63a726ea3d$export$d9b273488cd8ce6f = /*#__PURE__*/ (0, $aDee0$react).forwardRef(function Menu(props, ref) {
    let isSubmenu = true;
    let contextProps = (0, $aDee0$useContext)((0, $a4d1910f0ff9d033$export$c7e742effb1c51e2));
    let parentMenuContext = (0, $a4d1910f0ff9d033$export$efa3856fc0e85e7f)();
    let { rootMenuTriggerState: rootMenuTriggerState, state: parentMenuTreeState } = parentMenuContext || {
        rootMenuTriggerState: contextProps.state
    };
    if (!parentMenuContext) isSubmenu = false;
    let completeProps = {
        ...(0, $aDee0$mergeProps)(contextProps, props)
    };
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let [popoverContainer, setPopoverContainer] = (0, $aDee0$useState)(null);
    let trayContainerRef = (0, $aDee0$useRef)(null);
    let state = (0, $aDee0$useTreeState)(completeProps);
    let submenuRef = (0, $aDee0$useRef)(null);
    let { menuProps: menuProps } = (0, $aDee0$useMenu)(completeProps, state, domRef);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(completeProps);
    (0, $aDee0$useSyncRef)(contextProps, domRef);
    let [leftOffset, setLeftOffset] = (0, $aDee0$useState)({
        left: 0
    });
    let prevPopoverContainer = (0, $aDee0$useRef)(null);
    (0, $aDee0$useLayoutEffect)(()=>{
        if (popoverContainer && prevPopoverContainer.current !== popoverContainer && leftOffset.left === 0) {
            prevPopoverContainer.current = popoverContainer;
            let { left: left } = popoverContainer.getBoundingClientRect();
            setLeftOffset({
                left: -1 * left
            });
        }
    }, [
        leftOffset,
        popoverContainer
    ]);
    var _contextProps_submenuLevel;
    let menuLevel = (_contextProps_submenuLevel = contextProps.submenuLevel) !== null && _contextProps_submenuLevel !== void 0 ? _contextProps_submenuLevel : -1;
    let nextMenuLevelKey = rootMenuTriggerState === null || rootMenuTriggerState === void 0 ? void 0 : rootMenuTriggerState.expandedKeysStack[menuLevel + 1];
    let hasOpenSubmenu = false;
    if (nextMenuLevelKey != null) {
        let nextMenuLevel = state.collection.getItem(nextMenuLevelKey);
        hasOpenSubmenu = nextMenuLevel != null;
    }
    return /*#__PURE__*/ (0, $aDee0$react).createElement((0, $a4d1910f0ff9d033$export$24aad8519b95b41b).Provider, {
        value: {
            popoverContainer: popoverContainer,
            trayContainerRef: trayContainerRef,
            menu: domRef,
            submenu: submenuRef,
            rootMenuTriggerState: rootMenuTriggerState,
            state: state
        }
    }, /*#__PURE__*/ (0, $aDee0$react).createElement("div", {
        style: {
            height: hasOpenSubmenu ? '100%' : undefined
        },
        ref: trayContainerRef
    }), /*#__PURE__*/ (0, $aDee0$react).createElement((0, $aDee0$FocusScope), null, /*#__PURE__*/ (0, $aDee0$react).createElement($79ddee63a726ea3d$export$3dfe97b5c32d8d8c, {
        onBackButtonPress: contextProps.onBackButtonPress,
        hasOpenSubmenu: hasOpenSubmenu,
        isSubmenu: isSubmenu,
        parentMenuTreeState: parentMenuTreeState,
        rootMenuTriggerState: rootMenuTriggerState,
        menuRef: domRef
    }, /*#__PURE__*/ (0, $aDee0$react).createElement("div", {
        ...menuProps,
        style: (0, $aDee0$mergeProps)(styleProps.style, menuProps.style),
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aDee0$menu_vars_cssmjs))), 'spectrum-Menu', styleProps.className)
    }, [
        ...state.collection
    ].map((item)=>{
        if (item.type === 'section') return /*#__PURE__*/ (0, $aDee0$react).createElement((0, $0dc60032e766c118$export$4b1545b4f2016d26), {
            key: item.key,
            item: item,
            state: state
        });
        let menuItem = /*#__PURE__*/ (0, $aDee0$react).createElement((0, $53bbb287499fadf8$export$2ce376c2cc3355c8), {
            key: item.key,
            item: item,
            state: state
        });
        if (item.wrapper) menuItem = item.wrapper(menuItem);
        return menuItem;
    }))), (rootMenuTriggerState === null || rootMenuTriggerState === void 0 ? void 0 : rootMenuTriggerState.isOpen) && /*#__PURE__*/ (0, $aDee0$react).createElement("div", {
        ref: setPopoverContainer,
        style: {
            width: '100vw',
            position: 'absolute',
            top: -5,
            ...leftOffset
        }
    })));
});
function $79ddee63a726ea3d$export$3dfe97b5c32d8d8c(props) {
    var _parentMenuTreeState_collection_getItem;
    let { children: children, isSubmenu: isSubmenu, hasOpenSubmenu: hasOpenSubmenu, parentMenuTreeState: parentMenuTreeState, rootMenuTriggerState: rootMenuTriggerState, onBackButtonPress: onBackButtonPress, wrapperKeyDown: wrapperKeyDown, menuRef: menuRef } = props;
    let stringFormatter = (0, $aDee0$useLocalizedStringFormatter)((0, ($parcel$interopDefault($aDee0$intlStringsjs))), '@react-spectrum/menu');
    let lastKey = rootMenuTriggerState === null || rootMenuTriggerState === void 0 ? void 0 : rootMenuTriggerState.expandedKeysStack.slice(-1)[0];
    let backButtonText = '';
    var _parentMenuTreeState_collection_getItem_textValue;
    if (lastKey != null) backButtonText = (_parentMenuTreeState_collection_getItem_textValue = parentMenuTreeState === null || parentMenuTreeState === void 0 ? void 0 : (_parentMenuTreeState_collection_getItem = parentMenuTreeState.collection.getItem(lastKey)) === null || _parentMenuTreeState_collection_getItem === void 0 ? void 0 : _parentMenuTreeState_collection_getItem.textValue) !== null && _parentMenuTreeState_collection_getItem_textValue !== void 0 ? _parentMenuTreeState_collection_getItem_textValue : '';
    let backButtonLabel = stringFormatter.format('backButton', {
        prevMenuButton: backButtonText !== null && backButtonText !== void 0 ? backButtonText : ''
    });
    let headingId = (0, $aDee0$useSlotId)();
    let isMobile = (0, $196ab9279fe71c29$export$736bf165441b18c7)();
    let { direction: direction } = (0, $aDee0$useLocale)();
    let [traySubmenuAnimation, setTraySubmenuAnimation] = (0, $aDee0$useState)('');
    (0, $aDee0$useLayoutEffect)(()=>{
        if (!hasOpenSubmenu) setTraySubmenuAnimation('spectrum-TraySubmenu-enter');
    }, [
        hasOpenSubmenu,
        isMobile
    ]);
    let timeoutRef = (0, $aDee0$useRef)(null);
    let handleBackButtonPress = ()=>{
        setTraySubmenuAnimation('spectrum-TraySubmenu-exit');
        timeoutRef.current = setTimeout(()=>{
            onBackButtonPress === null || onBackButtonPress === void 0 ? void 0 : onBackButtonPress();
        }, 220); // Matches transition duration
    };
    (0, $aDee0$useEffect)(()=>{
        return ()=>{
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, []);
    // When opening submenu in tray, focus the first item in the submenu after animation completes
    // This fixes an issue with iOS VO where the closed submenu was getting focus
    let focusTimeoutRef = (0, $aDee0$useRef)(null);
    (0, $aDee0$useEffect)(()=>{
        if (isMobile && isSubmenu && !hasOpenSubmenu && traySubmenuAnimation === 'spectrum-TraySubmenu-enter') focusTimeoutRef.current = setTimeout(()=>{
            var _menuRef_current;
            let firstItem = menuRef === null || menuRef === void 0 ? void 0 : (_menuRef_current = menuRef.current) === null || _menuRef_current === void 0 ? void 0 : _menuRef_current.querySelector('[role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]');
            firstItem === null || firstItem === void 0 ? void 0 : firstItem.focus();
        }, 220);
        return ()=>{
            if (focusTimeoutRef.current) clearTimeout(focusTimeoutRef.current);
        };
    }, [
        hasOpenSubmenu,
        isMobile,
        isSubmenu,
        menuRef,
        traySubmenuAnimation
    ]);
    return /*#__PURE__*/ (0, $aDee0$react).createElement((0, $aDee0$react).Fragment, null, /*#__PURE__*/ (0, $aDee0$react).createElement("div", {
        role: headingId ? 'dialog' : undefined,
        "aria-labelledby": headingId,
        "aria-hidden": isMobile && hasOpenSubmenu,
        "data-testid": "menu-wrapper",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aDee0$menu_vars_cssmjs))), 'spectrum-Menu-wrapper', {
            'spectrum-Menu-wrapper--isMobile': isMobile,
            'is-expanded': hasOpenSubmenu,
            [traySubmenuAnimation]: isMobile
        })
    }, /*#__PURE__*/ (0, $aDee0$react).createElement("div", {
        role: "presentation",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aDee0$menu_vars_cssmjs))), 'spectrum-Submenu-wrapper', {
            'spectrum-Submenu-wrapper--isMobile': isMobile
        }),
        onKeyDown: wrapperKeyDown
    }, isMobile && isSubmenu && !hasOpenSubmenu && /*#__PURE__*/ (0, $aDee0$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aDee0$menu_vars_cssmjs))), 'spectrum-Submenu-headingWrapper')
    }, /*#__PURE__*/ (0, $aDee0$react).createElement((0, $c265dbb41bfd0210$export$cfc7921d29ef7b80), {
        "aria-label": backButtonLabel,
        isQuiet: true,
        onPress: handleBackButtonPress
    }, direction === 'rtl' ? /*#__PURE__*/ (0, $aDee0$react).createElement((0, $aDee0$spectrumiconsuiArrowDownSmall), {
        UNSAFE_style: {
            rotate: '270deg'
        }
    }) : /*#__PURE__*/ (0, $aDee0$react).createElement((0, $aDee0$spectrumiconsuiArrowDownSmall), {
        UNSAFE_style: {
            rotate: '90deg'
        }
    })), /*#__PURE__*/ (0, $aDee0$react).createElement("h1", {
        id: headingId,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($aDee0$menu_vars_cssmjs))), 'spectrum-Submenu-heading')
    }, backButtonText)), children)));
}


export {$79ddee63a726ea3d$export$d9b273488cd8ce6f as Menu, $79ddee63a726ea3d$export$3dfe97b5c32d8d8c as TrayHeaderWrapper};
//# sourceMappingURL=Menu.js.map
