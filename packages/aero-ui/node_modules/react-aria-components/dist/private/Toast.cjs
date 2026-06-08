var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $fKMmF$reactariauseToast = require("react-aria/useToast");
var $fKMmF$reactdom = require("react-dom");
var $fKMmF$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $fKMmF$reactariamergeProps = require("react-aria/mergeProps");
var $fKMmF$reactstatelyuseToastState = require("react-stately/useToastState");
var $fKMmF$react = require("react");
var $fKMmF$reactariauseFocusRing = require("react-aria/useFocusRing");
var $fKMmF$reactariauseHover = require("react-aria/useHover");
var $fKMmF$reactariaSSRProvider = require("react-aria/SSRProvider");
var $fKMmF$reactariaI18nProvider = require("react-aria/I18nProvider");
var $fKMmF$reactariauseObjectRef = require("react-aria/useObjectRef");
var $fKMmF$reactariaPortalProvider = require("react-aria/PortalProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "UNSTABLE_ToastStateContext", function () { return $2945ac8701b3c99f$export$17d647cb5858af3d; });
$parcel$export(module.exports, "UNSTABLE_ToastRegion", function () { return $2945ac8701b3c99f$export$133f5cbcf6f82aa1; });
$parcel$export(module.exports, "UNSTABLE_ToastList", function () { return $2945ac8701b3c99f$export$a40376c2ff45c041; });
$parcel$export(module.exports, "UNSTABLE_Toast", function () { return $2945ac8701b3c99f$export$200206268470a71; });
$parcel$export(module.exports, "UNSTABLE_ToastContent", function () { return $2945ac8701b3c99f$export$b134a6cc89b08851; });
/*
 * Copyright 2025 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 














const $2945ac8701b3c99f$export$17d647cb5858af3d = /*#__PURE__*/ (0, $fKMmF$react.createContext)(null);
/**
 * A ToastRegion displays one or more toast notifications.
 */ const $2945ac8701b3c99f$export$133f5cbcf6f82aa1 = /*#__PURE__*/ (0, $fKMmF$react.forwardRef)(function ToastRegion(props, ref) {
    let isSSR = (0, $fKMmF$reactariaSSRProvider.useIsSSR)();
    let state = (0, $fKMmF$reactstatelyuseToastState.useToastQueue)(props.queue);
    let objectRef = (0, $fKMmF$reactariauseObjectRef.useObjectRef)(ref);
    let { regionProps: regionProps } = (0, $fKMmF$reactariauseToast.useToastRegion)(props, state, objectRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $fKMmF$reactariauseFocusRing.useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $fKMmF$reactariauseHover.useHover)({});
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        children: undefined,
        defaultClassName: 'react-aria-ToastRegion',
        values: {
            visibleToasts: state.visibleToasts,
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        }
    });
    let { direction: direction } = (0, $fKMmF$reactariaI18nProvider.useLocale)();
    let portalContainer;
    let { getContainer: getContainer } = (0, $fKMmF$reactariaPortalProvider.useUNSAFE_PortalContext)();
    if (!isSSR) {
        portalContainer = document.body;
        if (getContainer) portalContainer = getContainer();
    }
    let DOMProps = (0, $fKMmF$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    let region = /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement($2945ac8701b3c99f$export$17d647cb5858af3d.Provider, {
        value: state
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $fKMmF$reactariamergeProps.mergeProps)(DOMProps, renderProps, regionProps, focusProps, hoverProps),
        dir: direction,
        ref: objectRef,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, typeof props.children === 'function' ? /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement($2945ac8701b3c99f$export$a40376c2ff45c041, {
        ...props,
        render: undefined,
        className: undefined,
        style: {
            display: 'contents'
        }
    }, props.children) : props.children));
    return state.visibleToasts.length > 0 && portalContainer ? /*#__PURE__*/ (0, $fKMmF$reactdom.createPortal)(region, portalContainer) : null;
});
const $2945ac8701b3c99f$export$a40376c2ff45c041 = /*#__PURE__*/ (0, $fKMmF$react.forwardRef)(function ToastList(props, ref) {
    let state = (0, $fKMmF$react.useContext)($2945ac8701b3c99f$export$17d647cb5858af3d);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $fKMmF$reactariauseHover.useHover)({});
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        children: undefined,
        defaultClassName: 'react-aria-ToastList',
        values: {
            visibleToasts: state.visibleToasts,
            isFocused: false,
            isFocusVisible: false,
            isHovered: isHovered
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement((0, $048d76b84370f141$exports.dom).ol, {
        ...hoverProps,
        ...renderProps,
        ref: ref
    }, state.visibleToasts.map((toast)=>/*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement("li", {
            key: toast.key,
            style: {
                display: 'contents'
            }
        }, props.children({
            toast: toast
        }))));
});
/**
 * A Toast displays a brief, temporary notification of actions, errors, or other events in an
 * application.
 */ const $2945ac8701b3c99f$export$200206268470a71 = /*#__PURE__*/ (0, $fKMmF$react.forwardRef)(function Toast(props, ref) {
    let state = (0, $fKMmF$react.useContext)($2945ac8701b3c99f$export$17d647cb5858af3d);
    let objectRef = (0, $fKMmF$reactariauseObjectRef.useObjectRef)(ref);
    let { toastProps: toastProps, contentProps: contentProps, titleProps: titleProps, descriptionProps: descriptionProps, closeButtonProps: closeButtonProps } = (0, $fKMmF$reactariauseToast.useToast)(props, state, objectRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $fKMmF$reactariauseFocusRing.useFocusRing)();
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-Toast',
        values: {
            toast: props.toast,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        }
    });
    let DOMProps = (0, $fKMmF$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $fKMmF$reactariamergeProps.mergeProps)(DOMProps, renderProps, toastProps, focusProps),
        ref: objectRef,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $2945ac8701b3c99f$export$3a0d85872d9f73f2,
                contentProps
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        [(0, $048d76b84370f141$exports.DEFAULT_SLOT)]: {},
                        title: titleProps,
                        description: descriptionProps
                    }
                }
            ],
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    slots: {
                        [(0, $048d76b84370f141$exports.DEFAULT_SLOT)]: {},
                        close: closeButtonProps
                    }
                }
            ]
        ]
    }, renderProps.children));
});
const $2945ac8701b3c99f$export$3a0d85872d9f73f2 = /*#__PURE__*/ (0, $fKMmF$react.createContext)({});
const $2945ac8701b3c99f$export$b134a6cc89b08851 = /*#__PURE__*/ (0, $fKMmF$react.forwardRef)(function ToastContent(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $2945ac8701b3c99f$export$3a0d85872d9f73f2);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($fKMmF$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        render: props.render,
        className: "react-aria-ToastContent",
        ...props,
        ref: ref
    }, props.children);
});


//# sourceMappingURL=Toast.cjs.map
