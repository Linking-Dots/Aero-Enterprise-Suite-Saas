import {ActionButton as $b41412308e87d8d9$export$cfc7921d29ef7b80} from "../button/ActionButton.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import $i9UTn$intlStringsmjs from "./intlStrings.mjs";
import {MenuContext as $9f4d05c8f96993f7$export$c7e742effb1c51e2, MenuStateContext as $9f4d05c8f96993f7$export$24aad8519b95b41b, useMenuStateContext as $9f4d05c8f96993f7$export$efa3856fc0e85e7f} from "./context.mjs";
import {MenuItem as $764fca59ff9a0c0a$export$2ce376c2cc3355c8} from "./MenuItem.mjs";
import {MenuSection as $8bf3363a4d12b22b$export$4b1545b4f2016d26} from "./MenuSection.mjs";
import "../menu_vars.css";
import $i9UTn$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useIsMobileDevice as $f357d4aae54bf1ff$export$736bf165441b18c7} from "../utils/useIsMobileDevice.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useMenu as $i9UTn$useMenu} from "react-aria/useMenu";
import $i9UTn$spectrumiconsuiArrowDownSmall from "@spectrum-icons/ui/ArrowDownSmall";
import {FocusScope as $i9UTn$FocusScope} from "react-aria/FocusScope";
import {mergeProps as $i9UTn$mergeProps} from "react-aria/mergeProps";
import $i9UTn$react, {useContext as $i9UTn$useContext, useState as $i9UTn$useState, useRef as $i9UTn$useRef, useEffect as $i9UTn$useEffect} from "react";
import {useTreeState as $i9UTn$useTreeState} from "react-stately/useTreeState";
import {useLayoutEffect as $i9UTn$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useLocale as $i9UTn$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $i9UTn$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useSlotId as $i9UTn$useSlotId} from "react-aria/private/utils/useId";
import {useSyncRef as $i9UTn$useSyncRef} from "react-aria/private/utils/useSyncRef";


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




















const $8cccdb0b63bfcdeb$export$d9b273488cd8ce6f = /*#__PURE__*/ (0, $i9UTn$react).forwardRef(function Menu(props, ref) {
    let isSubmenu = true;
    let contextProps = (0, $i9UTn$useContext)((0, $9f4d05c8f96993f7$export$c7e742effb1c51e2));
    let parentMenuContext = (0, $9f4d05c8f96993f7$export$efa3856fc0e85e7f)();
    let { rootMenuTriggerState: rootMenuTriggerState, state: parentMenuTreeState } = parentMenuContext || {
        rootMenuTriggerState: contextProps.state
    };
    if (!parentMenuContext) isSubmenu = false;
    let completeProps = {
        ...(0, $i9UTn$mergeProps)(contextProps, props)
    };
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let [popoverContainer, setPopoverContainer] = (0, $i9UTn$useState)(null);
    let trayContainerRef = (0, $i9UTn$useRef)(null);
    let state = (0, $i9UTn$useTreeState)(completeProps);
    let submenuRef = (0, $i9UTn$useRef)(null);
    let { menuProps: menuProps } = (0, $i9UTn$useMenu)(completeProps, state, domRef);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(completeProps);
    (0, $i9UTn$useSyncRef)(contextProps, domRef);
    let [leftOffset, setLeftOffset] = (0, $i9UTn$useState)({
        left: 0
    });
    let prevPopoverContainer = (0, $i9UTn$useRef)(null);
    (0, $i9UTn$useLayoutEffect)(()=>{
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
    let menuLevel = contextProps.submenuLevel ?? -1;
    let nextMenuLevelKey = rootMenuTriggerState?.expandedKeysStack[menuLevel + 1];
    let hasOpenSubmenu = false;
    if (nextMenuLevelKey != null) {
        let nextMenuLevel = state.collection.getItem(nextMenuLevelKey);
        hasOpenSubmenu = nextMenuLevel != null;
    }
    return /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $9f4d05c8f96993f7$export$24aad8519b95b41b).Provider, {
        value: {
            popoverContainer: popoverContainer,
            trayContainerRef: trayContainerRef,
            menu: domRef,
            submenu: submenuRef,
            rootMenuTriggerState: rootMenuTriggerState,
            state: state
        }
    }, /*#__PURE__*/ (0, $i9UTn$react).createElement("div", {
        style: {
            height: hasOpenSubmenu ? '100%' : undefined
        },
        ref: trayContainerRef
    }), /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $i9UTn$FocusScope), null, /*#__PURE__*/ (0, $i9UTn$react).createElement($8cccdb0b63bfcdeb$export$3dfe97b5c32d8d8c, {
        onBackButtonPress: contextProps.onBackButtonPress,
        hasOpenSubmenu: hasOpenSubmenu,
        isSubmenu: isSubmenu,
        parentMenuTreeState: parentMenuTreeState,
        rootMenuTriggerState: rootMenuTriggerState,
        menuRef: domRef
    }, /*#__PURE__*/ (0, $i9UTn$react).createElement("div", {
        ...menuProps,
        style: (0, $i9UTn$mergeProps)(styleProps.style, menuProps.style),
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9UTn$menu_vars_cssmjs))), 'spectrum-Menu', styleProps.className)
    }, [
        ...state.collection
    ].map((item)=>{
        if (item.type === 'section') return /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $8bf3363a4d12b22b$export$4b1545b4f2016d26), {
            key: item.key,
            item: item,
            state: state
        });
        let menuItem = /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $764fca59ff9a0c0a$export$2ce376c2cc3355c8), {
            key: item.key,
            item: item,
            state: state
        });
        if (item.wrapper) menuItem = item.wrapper(menuItem);
        return menuItem;
    }))), rootMenuTriggerState?.isOpen && /*#__PURE__*/ (0, $i9UTn$react).createElement("div", {
        ref: setPopoverContainer,
        style: {
            width: '100vw',
            position: 'absolute',
            top: -5,
            ...leftOffset
        }
    })));
});
function $8cccdb0b63bfcdeb$export$3dfe97b5c32d8d8c(props) {
    let { children: children, isSubmenu: isSubmenu, hasOpenSubmenu: hasOpenSubmenu, parentMenuTreeState: parentMenuTreeState, rootMenuTriggerState: rootMenuTriggerState, onBackButtonPress: onBackButtonPress, wrapperKeyDown: wrapperKeyDown, menuRef: menuRef } = props;
    let stringFormatter = (0, $i9UTn$useLocalizedStringFormatter)((0, ($parcel$interopDefault($i9UTn$intlStringsmjs))), '@react-spectrum/menu');
    let lastKey = rootMenuTriggerState?.expandedKeysStack.slice(-1)[0];
    let backButtonText = '';
    if (lastKey != null) backButtonText = parentMenuTreeState?.collection.getItem(lastKey)?.textValue ?? '';
    let backButtonLabel = stringFormatter.format('backButton', {
        prevMenuButton: backButtonText ?? ''
    });
    let headingId = (0, $i9UTn$useSlotId)();
    let isMobile = (0, $f357d4aae54bf1ff$export$736bf165441b18c7)();
    let { direction: direction } = (0, $i9UTn$useLocale)();
    let [traySubmenuAnimation, setTraySubmenuAnimation] = (0, $i9UTn$useState)('');
    (0, $i9UTn$useLayoutEffect)(()=>{
        if (!hasOpenSubmenu) setTraySubmenuAnimation('spectrum-TraySubmenu-enter');
    }, [
        hasOpenSubmenu,
        isMobile
    ]);
    let timeoutRef = (0, $i9UTn$useRef)(null);
    let handleBackButtonPress = ()=>{
        setTraySubmenuAnimation('spectrum-TraySubmenu-exit');
        timeoutRef.current = setTimeout(()=>{
            onBackButtonPress?.();
        }, 220); // Matches transition duration
    };
    (0, $i9UTn$useEffect)(()=>{
        return ()=>{
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, []);
    // When opening submenu in tray, focus the first item in the submenu after animation completes
    // This fixes an issue with iOS VO where the closed submenu was getting focus
    let focusTimeoutRef = (0, $i9UTn$useRef)(null);
    (0, $i9UTn$useEffect)(()=>{
        if (isMobile && isSubmenu && !hasOpenSubmenu && traySubmenuAnimation === 'spectrum-TraySubmenu-enter') focusTimeoutRef.current = setTimeout(()=>{
            let firstItem = menuRef?.current?.querySelector('[role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]');
            firstItem?.focus();
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
    return /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $i9UTn$react).Fragment, null, /*#__PURE__*/ (0, $i9UTn$react).createElement("div", {
        role: headingId ? 'dialog' : undefined,
        "aria-labelledby": headingId,
        "aria-hidden": isMobile && hasOpenSubmenu,
        "data-testid": "menu-wrapper",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9UTn$menu_vars_cssmjs))), 'spectrum-Menu-wrapper', {
            'spectrum-Menu-wrapper--isMobile': isMobile,
            'is-expanded': hasOpenSubmenu,
            [traySubmenuAnimation]: isMobile
        })
    }, /*#__PURE__*/ (0, $i9UTn$react).createElement("div", {
        role: "presentation",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9UTn$menu_vars_cssmjs))), 'spectrum-Submenu-wrapper', {
            'spectrum-Submenu-wrapper--isMobile': isMobile
        }),
        onKeyDown: wrapperKeyDown
    }, isMobile && isSubmenu && !hasOpenSubmenu && /*#__PURE__*/ (0, $i9UTn$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9UTn$menu_vars_cssmjs))), 'spectrum-Submenu-headingWrapper')
    }, /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $b41412308e87d8d9$export$cfc7921d29ef7b80), {
        "aria-label": backButtonLabel,
        isQuiet: true,
        onPress: handleBackButtonPress
    }, direction === 'rtl' ? /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $i9UTn$spectrumiconsuiArrowDownSmall), {
        UNSAFE_style: {
            rotate: '270deg'
        }
    }) : /*#__PURE__*/ (0, $i9UTn$react).createElement((0, $i9UTn$spectrumiconsuiArrowDownSmall), {
        UNSAFE_style: {
            rotate: '90deg'
        }
    })), /*#__PURE__*/ (0, $i9UTn$react).createElement("h1", {
        id: headingId,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9UTn$menu_vars_cssmjs))), 'spectrum-Submenu-heading')
    }, backButtonText)), children)));
}


export {$8cccdb0b63bfcdeb$export$d9b273488cd8ce6f as Menu, $8cccdb0b63bfcdeb$export$3dfe97b5c32d8d8c as TrayHeaderWrapper};
//# sourceMappingURL=Menu.mjs.map
