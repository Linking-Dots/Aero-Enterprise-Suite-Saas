import {ButtonContext as $fc203795b9b363cd$export$24d547caef80ccd1} from "./Button.js";
import {DEFAULT_SLOT as $b7b7a92703138c9b$export$c62b8e45d58ddad9, dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useToastRegion as $c2SYO$useToastRegion, useToast as $c2SYO$useToast} from "react-aria/useToast";
import {createPortal as $c2SYO$createPortal} from "react-dom";
import {filterDOMProps as $c2SYO$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $c2SYO$mergeProps} from "react-aria/mergeProps";
import {useToastQueue as $c2SYO$useToastQueue} from "react-stately/useToastState";
import $c2SYO$react, {createContext as $c2SYO$createContext, forwardRef as $c2SYO$forwardRef, useContext as $c2SYO$useContext} from "react";
import {useFocusRing as $c2SYO$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $c2SYO$useHover} from "react-aria/useHover";
import {useIsSSR as $c2SYO$useIsSSR} from "react-aria/SSRProvider";
import {useLocale as $c2SYO$useLocale} from "react-aria/I18nProvider";
import {useObjectRef as $c2SYO$useObjectRef} from "react-aria/useObjectRef";
import {useUNSAFE_PortalContext as $c2SYO$useUNSAFE_PortalContext} from "react-aria/PortalProvider";

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














const $a573cc55d7efc0c0$export$17d647cb5858af3d = /*#__PURE__*/ (0, $c2SYO$createContext)(null);
/**
 * A ToastRegion displays one or more toast notifications.
 */ const $a573cc55d7efc0c0$export$133f5cbcf6f82aa1 = /*#__PURE__*/ (0, $c2SYO$forwardRef)(function ToastRegion(props, ref) {
    let isSSR = (0, $c2SYO$useIsSSR)();
    let state = (0, $c2SYO$useToastQueue)(props.queue);
    let objectRef = (0, $c2SYO$useObjectRef)(ref);
    let { regionProps: regionProps } = (0, $c2SYO$useToastRegion)(props, state, objectRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $c2SYO$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $c2SYO$useHover)({});
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let { direction: direction } = (0, $c2SYO$useLocale)();
    let portalContainer;
    let { getContainer: getContainer } = (0, $c2SYO$useUNSAFE_PortalContext)();
    if (!isSSR) {
        portalContainer = document.body;
        if (getContainer) portalContainer = getContainer();
    }
    let DOMProps = (0, $c2SYO$filterDOMProps)(props, {
        global: true
    });
    let region = /*#__PURE__*/ (0, $c2SYO$react).createElement($a573cc55d7efc0c0$export$17d647cb5858af3d.Provider, {
        value: state
    }, /*#__PURE__*/ (0, $c2SYO$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $c2SYO$mergeProps)(DOMProps, renderProps, regionProps, focusProps, hoverProps),
        dir: direction,
        ref: objectRef,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, typeof props.children === 'function' ? /*#__PURE__*/ (0, $c2SYO$react).createElement($a573cc55d7efc0c0$export$a40376c2ff45c041, {
        ...props,
        render: undefined,
        className: undefined,
        style: {
            display: 'contents'
        }
    }, props.children) : props.children));
    return state.visibleToasts.length > 0 && portalContainer ? /*#__PURE__*/ (0, $c2SYO$createPortal)(region, portalContainer) : null;
});
const $a573cc55d7efc0c0$export$a40376c2ff45c041 = /*#__PURE__*/ (0, $c2SYO$forwardRef)(function ToastList(props, ref) {
    let state = (0, $c2SYO$useContext)($a573cc55d7efc0c0$export$17d647cb5858af3d);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $c2SYO$useHover)({});
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $c2SYO$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).ol, {
        ...hoverProps,
        ...renderProps,
        ref: ref
    }, state.visibleToasts.map((toast)=>/*#__PURE__*/ (0, $c2SYO$react).createElement("li", {
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
 */ const $a573cc55d7efc0c0$export$200206268470a71 = /*#__PURE__*/ (0, $c2SYO$forwardRef)(function Toast(props, ref) {
    let state = (0, $c2SYO$useContext)($a573cc55d7efc0c0$export$17d647cb5858af3d);
    let objectRef = (0, $c2SYO$useObjectRef)(ref);
    let { toastProps: toastProps, contentProps: contentProps, titleProps: titleProps, descriptionProps: descriptionProps, closeButtonProps: closeButtonProps } = (0, $c2SYO$useToast)(props, state, objectRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $c2SYO$useFocusRing)();
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-Toast',
        values: {
            toast: props.toast,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        }
    });
    let DOMProps = (0, $c2SYO$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $c2SYO$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $c2SYO$mergeProps)(DOMProps, renderProps, toastProps, focusProps),
        ref: objectRef,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, $c2SYO$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $a573cc55d7efc0c0$export$3a0d85872d9f73f2,
                contentProps
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        [(0, $b7b7a92703138c9b$export$c62b8e45d58ddad9)]: {},
                        title: titleProps,
                        description: descriptionProps
                    }
                }
            ],
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    slots: {
                        [(0, $b7b7a92703138c9b$export$c62b8e45d58ddad9)]: {},
                        close: closeButtonProps
                    }
                }
            ]
        ]
    }, renderProps.children));
});
const $a573cc55d7efc0c0$export$3a0d85872d9f73f2 = /*#__PURE__*/ (0, $c2SYO$createContext)({});
const $a573cc55d7efc0c0$export$b134a6cc89b08851 = /*#__PURE__*/ (0, $c2SYO$forwardRef)(function ToastContent(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $a573cc55d7efc0c0$export$3a0d85872d9f73f2);
    return /*#__PURE__*/ (0, $c2SYO$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        render: props.render,
        className: "react-aria-ToastContent",
        ...props,
        ref: ref
    }, props.children);
});


export {$a573cc55d7efc0c0$export$17d647cb5858af3d as UNSTABLE_ToastStateContext, $a573cc55d7efc0c0$export$133f5cbcf6f82aa1 as UNSTABLE_ToastRegion, $a573cc55d7efc0c0$export$a40376c2ff45c041 as UNSTABLE_ToastList, $a573cc55d7efc0c0$export$200206268470a71 as UNSTABLE_Toast, $a573cc55d7efc0c0$export$3a0d85872d9f73f2 as ToastContentContext, $a573cc55d7efc0c0$export$b134a6cc89b08851 as ToastContent, $a573cc55d7efc0c0$export$b134a6cc89b08851 as UNSTABLE_ToastContent};
//# sourceMappingURL=Toast.js.map
