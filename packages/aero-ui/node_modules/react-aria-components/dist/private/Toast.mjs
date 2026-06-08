import {ButtonContext as $7705c033048f6da7$export$24d547caef80ccd1} from "./Button.mjs";
import {DEFAULT_SLOT as $7230ffa83bc0c2cf$export$c62b8e45d58ddad9, dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useToastRegion as $jzto3$useToastRegion, useToast as $jzto3$useToast} from "react-aria/useToast";
import {createPortal as $jzto3$createPortal} from "react-dom";
import {filterDOMProps as $jzto3$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $jzto3$mergeProps} from "react-aria/mergeProps";
import {useToastQueue as $jzto3$useToastQueue} from "react-stately/useToastState";
import $jzto3$react, {createContext as $jzto3$createContext, forwardRef as $jzto3$forwardRef, useContext as $jzto3$useContext} from "react";
import {useFocusRing as $jzto3$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $jzto3$useHover} from "react-aria/useHover";
import {useIsSSR as $jzto3$useIsSSR} from "react-aria/SSRProvider";
import {useLocale as $jzto3$useLocale} from "react-aria/I18nProvider";
import {useObjectRef as $jzto3$useObjectRef} from "react-aria/useObjectRef";
import {useUNSAFE_PortalContext as $jzto3$useUNSAFE_PortalContext} from "react-aria/PortalProvider";

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














const $c92dc34ff52981e1$export$17d647cb5858af3d = /*#__PURE__*/ (0, $jzto3$createContext)(null);
/**
 * A ToastRegion displays one or more toast notifications.
 */ const $c92dc34ff52981e1$export$133f5cbcf6f82aa1 = /*#__PURE__*/ (0, $jzto3$forwardRef)(function ToastRegion(props, ref) {
    let isSSR = (0, $jzto3$useIsSSR)();
    let state = (0, $jzto3$useToastQueue)(props.queue);
    let objectRef = (0, $jzto3$useObjectRef)(ref);
    let { regionProps: regionProps } = (0, $jzto3$useToastRegion)(props, state, objectRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $jzto3$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $jzto3$useHover)({});
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    let { direction: direction } = (0, $jzto3$useLocale)();
    let portalContainer;
    let { getContainer: getContainer } = (0, $jzto3$useUNSAFE_PortalContext)();
    if (!isSSR) {
        portalContainer = document.body;
        if (getContainer) portalContainer = getContainer();
    }
    let DOMProps = (0, $jzto3$filterDOMProps)(props, {
        global: true
    });
    let region = /*#__PURE__*/ (0, $jzto3$react).createElement($c92dc34ff52981e1$export$17d647cb5858af3d.Provider, {
        value: state
    }, /*#__PURE__*/ (0, $jzto3$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $jzto3$mergeProps)(DOMProps, renderProps, regionProps, focusProps, hoverProps),
        dir: direction,
        ref: objectRef,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, typeof props.children === 'function' ? /*#__PURE__*/ (0, $jzto3$react).createElement($c92dc34ff52981e1$export$a40376c2ff45c041, {
        ...props,
        render: undefined,
        className: undefined,
        style: {
            display: 'contents'
        }
    }, props.children) : props.children));
    return state.visibleToasts.length > 0 && portalContainer ? /*#__PURE__*/ (0, $jzto3$createPortal)(region, portalContainer) : null;
});
const $c92dc34ff52981e1$export$a40376c2ff45c041 = /*#__PURE__*/ (0, $jzto3$forwardRef)(function ToastList(props, ref) {
    let state = (0, $jzto3$useContext)($c92dc34ff52981e1$export$17d647cb5858af3d);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $jzto3$useHover)({});
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $jzto3$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).ol, {
        ...hoverProps,
        ...renderProps,
        ref: ref
    }, state.visibleToasts.map((toast)=>/*#__PURE__*/ (0, $jzto3$react).createElement("li", {
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
 */ const $c92dc34ff52981e1$export$200206268470a71 = /*#__PURE__*/ (0, $jzto3$forwardRef)(function Toast(props, ref) {
    let state = (0, $jzto3$useContext)($c92dc34ff52981e1$export$17d647cb5858af3d);
    let objectRef = (0, $jzto3$useObjectRef)(ref);
    let { toastProps: toastProps, contentProps: contentProps, titleProps: titleProps, descriptionProps: descriptionProps, closeButtonProps: closeButtonProps } = (0, $jzto3$useToast)(props, state, objectRef);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $jzto3$useFocusRing)();
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-Toast',
        values: {
            toast: props.toast,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        }
    });
    let DOMProps = (0, $jzto3$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $jzto3$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $jzto3$mergeProps)(DOMProps, renderProps, toastProps, focusProps),
        ref: objectRef,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, $jzto3$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $c92dc34ff52981e1$export$3a0d85872d9f73f2,
                contentProps
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        [(0, $7230ffa83bc0c2cf$export$c62b8e45d58ddad9)]: {},
                        title: titleProps,
                        description: descriptionProps
                    }
                }
            ],
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    slots: {
                        [(0, $7230ffa83bc0c2cf$export$c62b8e45d58ddad9)]: {},
                        close: closeButtonProps
                    }
                }
            ]
        ]
    }, renderProps.children));
});
const $c92dc34ff52981e1$export$3a0d85872d9f73f2 = /*#__PURE__*/ (0, $jzto3$createContext)({});
const $c92dc34ff52981e1$export$b134a6cc89b08851 = /*#__PURE__*/ (0, $jzto3$forwardRef)(function ToastContent(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $c92dc34ff52981e1$export$3a0d85872d9f73f2);
    return /*#__PURE__*/ (0, $jzto3$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        render: props.render,
        className: "react-aria-ToastContent",
        ...props,
        ref: ref
    }, props.children);
});


export {$c92dc34ff52981e1$export$17d647cb5858af3d as UNSTABLE_ToastStateContext, $c92dc34ff52981e1$export$133f5cbcf6f82aa1 as UNSTABLE_ToastRegion, $c92dc34ff52981e1$export$a40376c2ff45c041 as UNSTABLE_ToastList, $c92dc34ff52981e1$export$200206268470a71 as UNSTABLE_Toast, $c92dc34ff52981e1$export$3a0d85872d9f73f2 as ToastContentContext, $c92dc34ff52981e1$export$b134a6cc89b08851 as ToastContent, $c92dc34ff52981e1$export$b134a6cc89b08851 as UNSTABLE_ToastContent};
//# sourceMappingURL=Toast.mjs.map
