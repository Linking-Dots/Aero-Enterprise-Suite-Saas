var $183c5173677598aa$exports = require("../button/ActionButton.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $e609f7c27f409c35$exports = require("./intlStrings.cjs");
var $827c4fa3df3c822d$exports = require("./context.cjs");
var $f98c72ac58c30ee0$exports = require("./MenuItem.cjs");
var $58c7a0147e48c32f$exports = require("./MenuSection.cjs");
require("../menu_vars.css");
var $35d34152ff885d5c$exports = require("../menu_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $0b97cdf6ccc1e502$exports = require("../utils/useIsMobileDevice.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $gBx7q$reactariauseMenu = require("react-aria/useMenu");
var $gBx7q$spectrumiconsuiArrowDownSmall = require("@spectrum-icons/ui/ArrowDownSmall");
var $gBx7q$reactariaFocusScope = require("react-aria/FocusScope");
var $gBx7q$reactariamergeProps = require("react-aria/mergeProps");
var $gBx7q$react = require("react");
var $gBx7q$reactstatelyuseTreeState = require("react-stately/useTreeState");
var $gBx7q$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $gBx7q$reactariaI18nProvider = require("react-aria/I18nProvider");
var $gBx7q$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");
var $gBx7q$reactariaprivateutilsuseId = require("react-aria/private/utils/useId");
var $gBx7q$reactariaprivateutilsuseSyncRef = require("react-aria/private/utils/useSyncRef");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Menu", function () { return $802fb5441f76e7b0$export$d9b273488cd8ce6f; });
$parcel$export(module.exports, "TrayHeaderWrapper", function () { return $802fb5441f76e7b0$export$3dfe97b5c32d8d8c; });
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




















const $802fb5441f76e7b0$export$d9b273488cd8ce6f = /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).forwardRef(function Menu(props, ref) {
    let isSubmenu = true;
    let contextProps = (0, $gBx7q$react.useContext)((0, $827c4fa3df3c822d$exports.MenuContext));
    let parentMenuContext = (0, $827c4fa3df3c822d$exports.useMenuStateContext)();
    let { rootMenuTriggerState: rootMenuTriggerState, state: parentMenuTreeState } = parentMenuContext || {
        rootMenuTriggerState: contextProps.state
    };
    if (!parentMenuContext) isSubmenu = false;
    let completeProps = {
        ...(0, $gBx7q$reactariamergeProps.mergeProps)(contextProps, props)
    };
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let [popoverContainer, setPopoverContainer] = (0, $gBx7q$react.useState)(null);
    let trayContainerRef = (0, $gBx7q$react.useRef)(null);
    let state = (0, $gBx7q$reactstatelyuseTreeState.useTreeState)(completeProps);
    let submenuRef = (0, $gBx7q$react.useRef)(null);
    let { menuProps: menuProps } = (0, $gBx7q$reactariauseMenu.useMenu)(completeProps, state, domRef);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(completeProps);
    (0, $gBx7q$reactariaprivateutilsuseSyncRef.useSyncRef)(contextProps, domRef);
    let [leftOffset, setLeftOffset] = (0, $gBx7q$react.useState)({
        left: 0
    });
    let prevPopoverContainer = (0, $gBx7q$react.useRef)(null);
    (0, $gBx7q$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
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
    return /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, $827c4fa3df3c822d$exports.MenuStateContext).Provider, {
        value: {
            popoverContainer: popoverContainer,
            trayContainerRef: trayContainerRef,
            menu: domRef,
            submenu: submenuRef,
            rootMenuTriggerState: rootMenuTriggerState,
            state: state
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("div", {
        style: {
            height: hasOpenSubmenu ? '100%' : undefined
        },
        ref: trayContainerRef
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, $gBx7q$reactariaFocusScope.FocusScope), null, /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement($802fb5441f76e7b0$export$3dfe97b5c32d8d8c, {
        onBackButtonPress: contextProps.onBackButtonPress,
        hasOpenSubmenu: hasOpenSubmenu,
        isSubmenu: isSubmenu,
        parentMenuTreeState: parentMenuTreeState,
        rootMenuTriggerState: rootMenuTriggerState,
        menuRef: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("div", {
        ...menuProps,
        style: (0, $gBx7q$reactariamergeProps.mergeProps)(styleProps.style, menuProps.style),
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu', styleProps.className)
    }, [
        ...state.collection
    ].map((item)=>{
        if (item.type === 'section') return /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, $58c7a0147e48c32f$exports.MenuSection), {
            key: item.key,
            item: item,
            state: state
        });
        let menuItem = /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, $f98c72ac58c30ee0$exports.MenuItem), {
            key: item.key,
            item: item,
            state: state
        });
        if (item.wrapper) menuItem = item.wrapper(menuItem);
        return menuItem;
    }))), rootMenuTriggerState?.isOpen && /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("div", {
        ref: setPopoverContainer,
        style: {
            width: '100vw',
            position: 'absolute',
            top: -5,
            ...leftOffset
        }
    })));
});
function $802fb5441f76e7b0$export$3dfe97b5c32d8d8c(props) {
    let { children: children, isSubmenu: isSubmenu, hasOpenSubmenu: hasOpenSubmenu, parentMenuTreeState: parentMenuTreeState, rootMenuTriggerState: rootMenuTriggerState, onBackButtonPress: onBackButtonPress, wrapperKeyDown: wrapperKeyDown, menuRef: menuRef } = props;
    let stringFormatter = (0, $gBx7q$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($e609f7c27f409c35$exports))), '@react-spectrum/menu');
    let lastKey = rootMenuTriggerState?.expandedKeysStack.slice(-1)[0];
    let backButtonText = '';
    if (lastKey != null) backButtonText = parentMenuTreeState?.collection.getItem(lastKey)?.textValue ?? '';
    let backButtonLabel = stringFormatter.format('backButton', {
        prevMenuButton: backButtonText ?? ''
    });
    let headingId = (0, $gBx7q$reactariaprivateutilsuseId.useSlotId)();
    let isMobile = (0, $0b97cdf6ccc1e502$exports.useIsMobileDevice)();
    let { direction: direction } = (0, $gBx7q$reactariaI18nProvider.useLocale)();
    let [traySubmenuAnimation, setTraySubmenuAnimation] = (0, $gBx7q$react.useState)('');
    (0, $gBx7q$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        if (!hasOpenSubmenu) setTraySubmenuAnimation('spectrum-TraySubmenu-enter');
    }, [
        hasOpenSubmenu,
        isMobile
    ]);
    let timeoutRef = (0, $gBx7q$react.useRef)(null);
    let handleBackButtonPress = ()=>{
        setTraySubmenuAnimation('spectrum-TraySubmenu-exit');
        timeoutRef.current = setTimeout(()=>{
            onBackButtonPress?.();
        }, 220); // Matches transition duration
    };
    (0, $gBx7q$react.useEffect)(()=>{
        return ()=>{
            if (timeoutRef.current) clearTimeout(timeoutRef.current);
        };
    }, []);
    // When opening submenu in tray, focus the first item in the submenu after animation completes
    // This fixes an issue with iOS VO where the closed submenu was getting focus
    let focusTimeoutRef = (0, $gBx7q$react.useRef)(null);
    (0, $gBx7q$react.useEffect)(()=>{
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
    return /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, ($parcel$interopDefault($gBx7q$react))).Fragment, null, /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("div", {
        role: headingId ? 'dialog' : undefined,
        "aria-labelledby": headingId,
        "aria-hidden": isMobile && hasOpenSubmenu,
        "data-testid": "menu-wrapper",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-wrapper', {
            'spectrum-Menu-wrapper--isMobile': isMobile,
            'is-expanded': hasOpenSubmenu,
            [traySubmenuAnimation]: isMobile
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("div", {
        role: "presentation",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Submenu-wrapper', {
            'spectrum-Submenu-wrapper--isMobile': isMobile
        }),
        onKeyDown: wrapperKeyDown
    }, isMobile && isSubmenu && !hasOpenSubmenu && /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Submenu-headingWrapper')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, $183c5173677598aa$exports.ActionButton), {
        "aria-label": backButtonLabel,
        isQuiet: true,
        onPress: handleBackButtonPress
    }, direction === 'rtl' ? /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, ($parcel$interopDefault($gBx7q$spectrumiconsuiArrowDownSmall))), {
        UNSAFE_style: {
            rotate: '270deg'
        }
    }) : /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement((0, ($parcel$interopDefault($gBx7q$spectrumiconsuiArrowDownSmall))), {
        UNSAFE_style: {
            rotate: '90deg'
        }
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($gBx7q$react))).createElement("h1", {
        id: headingId,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Submenu-heading')
    }, backButtonText)), children)));
}


//# sourceMappingURL=Menu.cjs.map
