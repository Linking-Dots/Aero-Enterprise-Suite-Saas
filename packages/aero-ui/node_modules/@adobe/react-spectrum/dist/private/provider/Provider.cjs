var $bb33895bbbdc8bdb$exports = require("../utils/BreakpointProvider.cjs");
var $c696f81f242b670e$exports = require("./context.cjs");
var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../page_vars.css");
var $bd19168d858dcc5f$exports = require("../page_vars_css.cjs");
require("../typography_index.css");
var $a6bc8488685ed47c$exports = require("../typography_index_css.cjs");
var $5d920a4b4d4fa5d5$exports = require("./mediaQueries.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $79c7898f5421a755$exports = require("../package.cjs");
var $eVc0A$clsx = require("clsx");
var $eVc0A$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $eVc0A$reactariaI18nProvider = require("react-aria/I18nProvider");
var $eVc0A$reactariaprivateoverlaysuseModal = require("react-aria/private/overlays/useModal");
var $eVc0A$react = require("react");
var $eVc0A$reactariaprivateutilsopenLink = require("react-aria/private/utils/openLink");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Provider", function () { return $544fc82701fc93e9$export$2881499e37b75b9a; });
$parcel$export(module.exports, "useProvider", function () { return $544fc82701fc93e9$export$693cdb10cec23617; });
$parcel$export(module.exports, "useProviderProps", function () { return $544fc82701fc93e9$export$521c373ccc32c300; });
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














const $544fc82701fc93e9$var$DEFAULT_BREAKPOINTS = {
    S: 640,
    M: 768,
    L: 1024,
    XL: 1280,
    XXL: 1536
};
const $544fc82701fc93e9$export$2881499e37b75b9a = /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).forwardRef(function Provider(props, ref) {
    let prevContext = (0, $eVc0A$react.useContext)((0, $c696f81f242b670e$exports.Context));
    let prevColorScheme = prevContext && prevContext.colorScheme;
    let prevBreakpoints = prevContext && prevContext.breakpoints;
    let { theme: theme = prevContext && prevContext.theme, defaultColorScheme: defaultColorScheme } = props;
    if (!theme) throw new Error('theme not found, the parent provider must have a theme provided');
    // Hooks must always be called.
    let autoColorScheme = (0, $5d920a4b4d4fa5d5$exports.useColorScheme)(theme, defaultColorScheme || 'light');
    let autoScale = (0, $5d920a4b4d4fa5d5$exports.useScale)(theme);
    let { locale: prevLocale } = (0, $eVc0A$reactariaI18nProvider.useLocale)();
    // if the new theme doesn't support the prevColorScheme, we must resort to the auto
    let usePrevColorScheme = prevColorScheme ? !!theme[prevColorScheme] : false;
    // importance of color scheme props > parent > auto:(OS > default > omitted)
    let { colorScheme: colorScheme = usePrevColorScheme ? prevColorScheme : autoColorScheme, scale: scale = prevContext ? prevContext.scale : autoScale, locale: locale = prevContext ? prevLocale : undefined, breakpoints: breakpoints = prevContext ? prevBreakpoints : $544fc82701fc93e9$var$DEFAULT_BREAKPOINTS, children: children, isQuiet: isQuiet, isEmphasized: isEmphasized, isDisabled: isDisabled, isRequired: isRequired, isReadOnly: isReadOnly, validationState: validationState, router: router, ...otherProps } = props;
    // select only the props with values so undefined props don't overwrite prevContext values
    let currentProps = {
        version: $79c7898f5421a755$exports.version,
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
    let matchedBreakpoints = (0, $bb33895bbbdc8bdb$exports.useMatchedBreakpoints)(breakpoints);
    let filteredProps = {};
    Object.entries(currentProps).forEach(([key, value])=>value !== undefined && (filteredProps[key] = value));
    // Merge options with parent provider
    let context = Object.assign({}, prevContext, filteredProps);
    // Only wrap in a DOM node if the theme, colorScheme, or scale changed
    let contents = children;
    let domProps = (0, $eVc0A$reactariafilterDOMProps.filterDOMProps)(otherProps);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps, undefined, {
        matchedBreakpoints: matchedBreakpoints
    });
    if (!prevContext || props.locale || theme !== prevContext.theme || colorScheme !== prevContext.colorScheme || scale !== prevContext.scale || Object.keys(domProps).length > 0 || otherProps.UNSAFE_className || styleProps.style && Object.keys(styleProps.style).length > 0) contents = /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement($544fc82701fc93e9$var$ProviderWrapper, {
        ...props,
        UNSAFE_style: {
            isolation: !prevContext ? 'isolate' : undefined,
            ...styleProps.style
        },
        ref: ref
    }, contents);
    if (router) contents = /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement((0, $eVc0A$reactariaprivateutilsopenLink.RouterProvider), router, contents);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement((0, $c696f81f242b670e$exports.Context).Provider, {
        value: context
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement((0, $eVc0A$reactariaI18nProvider.I18nProvider), {
        locale: locale
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement((0, $bb33895bbbdc8bdb$exports.BreakpointProvider), {
        matchedBreakpoints: matchedBreakpoints
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement((0, $eVc0A$reactariaprivateoverlaysuseModal.ModalProvider), null, contents))));
});
const $544fc82701fc93e9$var$ProviderWrapper = /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).forwardRef(function ProviderWrapper(props, ref) {
    let { children: children, ...otherProps } = props;
    let { locale: locale, direction: direction } = (0, $eVc0A$reactariaI18nProvider.useLocale)();
    let { theme: theme, colorScheme: colorScheme, scale: scale } = $544fc82701fc93e9$export$693cdb10cec23617();
    let { modalProviderProps: modalProviderProps } = (0, $eVc0A$reactariaprivateoverlaysuseModal.useModalProvider)();
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let themeKey = Object.keys(theme[colorScheme])[0];
    let scaleKey = Object.keys(theme[scale])[0];
    let className = (0, ($parcel$interopDefault($eVc0A$clsx)))(styleProps.className, (0, ($parcel$interopDefault($bd19168d858dcc5f$exports)))['spectrum'], (0, ($parcel$interopDefault($a6bc8488685ed47c$exports)))['spectrum'], Object.values(theme[colorScheme]), Object.values(theme[scale]), theme.global ? Object.values(theme.global) : null, {
        'react-spectrum-provider': (0, $69fd630bd812ba47$exports.shouldKeepSpectrumClassNames),
        spectrum: (0, $69fd630bd812ba47$exports.shouldKeepSpectrumClassNames),
        [themeKey]: (0, $69fd630bd812ba47$exports.shouldKeepSpectrumClassNames),
        [scaleKey]: (0, $69fd630bd812ba47$exports.shouldKeepSpectrumClassNames)
    });
    let style = {
        ...styleProps.style,
        // This ensures that browser native UI like scrollbars are rendered in the right color scheme.
        // See https://web.dev/color-scheme/.
        colorScheme: props.colorScheme ?? colorScheme ?? Object.keys(theme).filter((k)=>k === 'light' || k === 'dark').join(' ')
    };
    let hasWarned = (0, $eVc0A$react.useRef)(false);
    (0, $eVc0A$react.useEffect)(()=>{
        if (direction && domRef.current) {
            let closestDir = domRef.current?.parentElement?.closest('[dir]');
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
    return /*#__PURE__*/ (0, ($parcel$interopDefault($eVc0A$react))).createElement("div", {
        ...(0, $eVc0A$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        ...modalProviderProps,
        className: className,
        style: style,
        lang: locale,
        dir: direction,
        ref: domRef
    }, children);
});
function $544fc82701fc93e9$export$693cdb10cec23617() {
    let context = (0, $eVc0A$react.useContext)((0, $c696f81f242b670e$exports.Context));
    if (!context) throw new Error("No root provider found, please make sure your app is wrapped within a <Provider>. Alternatively, this issue may be caused by duplicate packages, see https://github.com/adobe/react-spectrum/wiki/Frequently-Asked-Questions-(FAQs)#why-are-there-errors-after-upgrading-a-react-spectrum-package for more information.");
    return context;
}
function $544fc82701fc93e9$export$521c373ccc32c300(props) {
    let context = (0, $eVc0A$react.useContext)((0, $c696f81f242b670e$exports.Context));
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


//# sourceMappingURL=Provider.cjs.map
