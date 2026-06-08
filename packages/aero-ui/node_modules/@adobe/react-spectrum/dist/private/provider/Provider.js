import {BreakpointProvider as $cf1a1f4b586658ed$export$8214320346cf5104, useMatchedBreakpoints as $cf1a1f4b586658ed$export$140ae7baa51cca23} from "../utils/BreakpointProvider.js";
import {Context as $9c45f53482a71c84$export$841858b892ce1f4c} from "./context.js";
import {shouldKeepSpectrumClassNames as $512ba93e663f149c$export$46d604dce8bf8724} from "../utils/classNames.js";
import "../page_vars.css";
import $evnYk$page_vars_cssmjs from "../page_vars_css.mjs";
import "../typography_index.css";
import $evnYk$typography_index_cssmjs from "../typography_index_css.mjs";
import {useColorScheme as $6ef8b835d1e952be$export$6343629ee1b29116, useScale as $6ef8b835d1e952be$export$a8d2043b2d807f4d} from "./mediaQueries.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $evnYk$packagejs from "../package.js";
import $evnYk$clsx from "clsx";
import {filterDOMProps as $evnYk$filterDOMProps} from "react-aria/filterDOMProps";
import {useLocale as $evnYk$useLocale, I18nProvider as $evnYk$I18nProvider} from "react-aria/I18nProvider";
import {ModalProvider as $evnYk$ModalProvider, useModalProvider as $evnYk$useModalProvider} from "react-aria/private/overlays/useModal";
import $evnYk$react, {useContext as $evnYk$useContext, useRef as $evnYk$useRef, useEffect as $evnYk$useEffect} from "react";
import {RouterProvider as $evnYk$RouterProvider} from "react-aria/private/utils/openLink";


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














const $089943c7a219141c$var$DEFAULT_BREAKPOINTS = {
    S: 640,
    M: 768,
    L: 1024,
    XL: 1280,
    XXL: 1536
};
const $089943c7a219141c$export$2881499e37b75b9a = /*#__PURE__*/ (0, $evnYk$react).forwardRef(function Provider(props, ref) {
    let prevContext = (0, $evnYk$useContext)((0, $9c45f53482a71c84$export$841858b892ce1f4c));
    let prevColorScheme = prevContext && prevContext.colorScheme;
    let prevBreakpoints = prevContext && prevContext.breakpoints;
    let { theme: theme = prevContext && prevContext.theme, defaultColorScheme: defaultColorScheme } = props;
    if (!theme) throw new Error('theme not found, the parent provider must have a theme provided');
    // Hooks must always be called.
    let autoColorScheme = (0, $6ef8b835d1e952be$export$6343629ee1b29116)(theme, defaultColorScheme || 'light');
    let autoScale = (0, $6ef8b835d1e952be$export$a8d2043b2d807f4d)(theme);
    let { locale: prevLocale } = (0, $evnYk$useLocale)();
    // if the new theme doesn't support the prevColorScheme, we must resort to the auto
    let usePrevColorScheme = prevColorScheme ? !!theme[prevColorScheme] : false;
    // importance of color scheme props > parent > auto:(OS > default > omitted)
    let { colorScheme: colorScheme = usePrevColorScheme ? prevColorScheme : autoColorScheme, scale: scale = prevContext ? prevContext.scale : autoScale, locale: locale = prevContext ? prevLocale : undefined, breakpoints: breakpoints = prevContext ? prevBreakpoints : $089943c7a219141c$var$DEFAULT_BREAKPOINTS, children: children, isQuiet: isQuiet, isEmphasized: isEmphasized, isDisabled: isDisabled, isRequired: isRequired, isReadOnly: isReadOnly, validationState: validationState, router: router, ...otherProps } = props;
    // select only the props with values so undefined props don't overwrite prevContext values
    let currentProps = {
        version: $evnYk$packagejs.version,
        theme: theme,
        breakpoints: breakpoints,
        colorScheme: colorScheme,
        scale: scale,
        isQuiet: isQuiet,
        isEmphasized: isEmphasized,
        isDisabled: isDisabled,
        isRequired: isRequired,
        isReadOnly: isReadOnly,
        validationState: validationState
    };
    let matchedBreakpoints = (0, $cf1a1f4b586658ed$export$140ae7baa51cca23)(breakpoints);
    let filteredProps = {};
    Object.entries(currentProps).forEach(([key, value])=>value !== undefined && (filteredProps[key] = value));
    // Merge options with parent provider
    let context = Object.assign({}, prevContext, filteredProps);
    // Only wrap in a DOM node if the theme, colorScheme, or scale changed
    let contents = children;
    let domProps = (0, $evnYk$filterDOMProps)(otherProps);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps, undefined, {
        matchedBreakpoints: matchedBreakpoints
    });
    if (!prevContext || props.locale || theme !== prevContext.theme || colorScheme !== prevContext.colorScheme || scale !== prevContext.scale || Object.keys(domProps).length > 0 || otherProps.UNSAFE_className || styleProps.style && Object.keys(styleProps.style).length > 0) contents = /*#__PURE__*/ (0, $evnYk$react).createElement($089943c7a219141c$var$ProviderWrapper, {
        ...props,
        UNSAFE_style: {
            isolation: !prevContext ? 'isolate' : undefined,
            ...styleProps.style
        },
        ref: ref
    }, contents);
    if (router) contents = /*#__PURE__*/ (0, $evnYk$react).createElement((0, $evnYk$RouterProvider), router, contents);
    return /*#__PURE__*/ (0, $evnYk$react).createElement((0, $9c45f53482a71c84$export$841858b892ce1f4c).Provider, {
        value: context
    }, /*#__PURE__*/ (0, $evnYk$react).createElement((0, $evnYk$I18nProvider), {
        locale: locale
    }, /*#__PURE__*/ (0, $evnYk$react).createElement((0, $cf1a1f4b586658ed$export$8214320346cf5104), {
        matchedBreakpoints: matchedBreakpoints
    }, /*#__PURE__*/ (0, $evnYk$react).createElement((0, $evnYk$ModalProvider), null, contents))));
});
const $089943c7a219141c$var$ProviderWrapper = /*#__PURE__*/ (0, $evnYk$react).forwardRef(function ProviderWrapper(props, ref) {
    let { children: children, ...otherProps } = props;
    let { locale: locale, direction: direction } = (0, $evnYk$useLocale)();
    let { theme: theme, colorScheme: colorScheme, scale: scale } = $089943c7a219141c$export$693cdb10cec23617();
    let { modalProviderProps: modalProviderProps } = (0, $evnYk$useModalProvider)();
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let themeKey = Object.keys(theme[colorScheme])[0];
    let scaleKey = Object.keys(theme[scale])[0];
    let className = (0, $evnYk$clsx)(styleProps.className, (0, ($parcel$interopDefault($evnYk$page_vars_cssmjs)))['spectrum'], (0, ($parcel$interopDefault($evnYk$typography_index_cssmjs)))['spectrum'], Object.values(theme[colorScheme]), Object.values(theme[scale]), theme.global ? Object.values(theme.global) : null, {
        'react-spectrum-provider': (0, $512ba93e663f149c$export$46d604dce8bf8724),
        spectrum: (0, $512ba93e663f149c$export$46d604dce8bf8724),
        [themeKey]: (0, $512ba93e663f149c$export$46d604dce8bf8724),
        [scaleKey]: (0, $512ba93e663f149c$export$46d604dce8bf8724)
    });
    var _props_colorScheme, _ref;
    let style = {
        ...styleProps.style,
        // This ensures that browser native UI like scrollbars are rendered in the right color scheme.
        // See https://web.dev/color-scheme/.
        colorScheme: (_ref = (_props_colorScheme = props.colorScheme) !== null && _props_colorScheme !== void 0 ? _props_colorScheme : colorScheme) !== null && _ref !== void 0 ? _ref : Object.keys(theme).filter((k)=>k === 'light' || k === 'dark').join(' ')
    };
    let hasWarned = (0, $evnYk$useRef)(false);
    (0, $evnYk$useEffect)(()=>{
        if (direction && domRef.current) {
            var _domRef_current_parentElement, _domRef_current;
            let closestDir = (_domRef_current = domRef.current) === null || _domRef_current === void 0 ? void 0 : (_domRef_current_parentElement = _domRef_current.parentElement) === null || _domRef_current_parentElement === void 0 ? void 0 : _domRef_current_parentElement.closest('[dir]');
            let dir = closestDir && closestDir.getAttribute('dir');
            if (dir && dir !== direction && !hasWarned.current && process.env.NODE_ENV !== 'production') {
                console.warn(`Language directions cannot be nested. ${direction} inside ${dir}.`);
                hasWarned.current = true;
            }
        }
    }, [
        direction,
        domRef,
        hasWarned
    ]);
    return /*#__PURE__*/ (0, $evnYk$react).createElement("div", {
        ...(0, $evnYk$filterDOMProps)(otherProps),
        ...styleProps,
        ...modalProviderProps,
        className: className,
        style: style,
        lang: locale,
        dir: direction,
        ref: domRef
    }, children);
});
function $089943c7a219141c$export$693cdb10cec23617() {
    let context = (0, $evnYk$useContext)((0, $9c45f53482a71c84$export$841858b892ce1f4c));
    if (!context) throw new Error("No root provider found, please make sure your app is wrapped within a <Provider>. Alternatively, this issue may be caused by duplicate packages, see https://github.com/adobe/react-spectrum/wiki/Frequently-Asked-Questions-(FAQs)#why-are-there-errors-after-upgrading-a-react-spectrum-package for more information.");
    return context;
}
function $089943c7a219141c$export$521c373ccc32c300(props) {
    let context = (0, $evnYk$useContext)((0, $9c45f53482a71c84$export$841858b892ce1f4c));
    if (!context) return props;
    return Object.assign({}, {
        isQuiet: context.isQuiet,
        isEmphasized: context.isEmphasized,
        isDisabled: context.isDisabled,
        isRequired: context.isRequired,
        isReadOnly: context.isReadOnly,
        validationState: context.validationState
    }, props);
}


export {$089943c7a219141c$export$2881499e37b75b9a as Provider, $089943c7a219141c$export$693cdb10cec23617 as useProvider, $089943c7a219141c$export$521c373ccc32c300 as useProviderProps};
//# sourceMappingURL=Provider.js.map
