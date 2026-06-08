import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Toast as $cfebc782ec37c3c6$export$8d8dc7d5f743331b} from "./Toast.mjs";
import "./toastContainer.css";
import $90KaU$toastContainer_cssmjs from "./toastContainer_css.mjs";
import {Toaster as $74a94cdc2e765763$export$fb98e3a2a4cd92d7} from "./Toaster.mjs";
import {filterDOMProps as $90KaU$filterDOMProps} from "react-aria/filterDOMProps";
import {flushSync as $90KaU$flushSync} from "react-dom";
import $90KaU$react, {useRef as $90KaU$useRef, useEffect as $90KaU$useEffect, useMemo as $90KaU$useMemo} from "react";
import {ToastQueue as $90KaU$ToastQueue, useToastQueue as $90KaU$useToastQueue} from "react-stately/useToastState";
import {useSyncExternalStore as $90KaU$useSyncExternalStore} from "use-sync-external-store/shim/index.js";


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








function $33add86cc52875f9$var$wrapInViewTransition(fn) {
    if ('startViewTransition' in document) document.startViewTransition(()=>{
        (0, $90KaU$flushSync)(fn);
    }).ready.catch(()=>{});
    else fn();
}
// There is a single global toast queue instance for the whole app, initialized lazily.
let $33add86cc52875f9$var$globalToastQueue = null;
function $33add86cc52875f9$var$getGlobalToastQueue() {
    if (!$33add86cc52875f9$var$globalToastQueue) $33add86cc52875f9$var$globalToastQueue = new (0, $90KaU$ToastQueue)({
        maxVisibleToasts: Infinity,
        wrapUpdate: $33add86cc52875f9$var$wrapInViewTransition
    });
    return $33add86cc52875f9$var$globalToastQueue;
}
function $33add86cc52875f9$export$320311f0e4ecb3ae() {
    $33add86cc52875f9$var$globalToastQueue = null;
}
let $33add86cc52875f9$var$toastProviders = new Set();
let $33add86cc52875f9$var$subscriptions = new Set();
function $33add86cc52875f9$var$subscribe(fn) {
    $33add86cc52875f9$var$subscriptions.add(fn);
    return ()=>$33add86cc52875f9$var$subscriptions.delete(fn);
}
function $33add86cc52875f9$var$triggerSubscriptions() {
    for (let fn of $33add86cc52875f9$var$subscriptions)fn();
}
function $33add86cc52875f9$var$getActiveToastContainer() {
    return $33add86cc52875f9$var$toastProviders.values().next().value;
}
function $33add86cc52875f9$var$useActiveToastContainer() {
    return (0, $90KaU$useSyncExternalStore)($33add86cc52875f9$var$subscribe, $33add86cc52875f9$var$getActiveToastContainer, $33add86cc52875f9$var$getActiveToastContainer);
}
function $33add86cc52875f9$export$f2815235e76a62b9(props) {
    // Track all toast provider instances in a set.
    // Only the first one will actually render.
    // We use a ref to do this, since it will have a stable identity
    // over the lifetime of the component.
    let ref = (0, $90KaU$useRef)(null);
    (0, $90KaU$useEffect)(()=>{
        $33add86cc52875f9$var$toastProviders.add(ref);
        $33add86cc52875f9$var$triggerSubscriptions();
        return ()=>{
            // Remove this toast provider, and call subscriptions.
            // This will cause all other instances to re-render,
            // and the first one to become the new active toast provider.
            $33add86cc52875f9$var$toastProviders.delete(ref);
            $33add86cc52875f9$var$triggerSubscriptions();
        };
    }, []);
    // Only render if this is the active toast provider instance, and there are visible toasts.
    let activeToastContainer = $33add86cc52875f9$var$useActiveToastContainer();
    let state = (0, $90KaU$useToastQueue)($33add86cc52875f9$var$getGlobalToastQueue());
    let { placement: placement, isCentered: isCentered } = (0, $90KaU$useMemo)(()=>{
        let placements = (props.placement ?? 'bottom').split(' ');
        let placement = placements[placements.length - 1];
        let isCentered = placements.length === 1;
        return {
            placement: placement,
            isCentered: isCentered
        };
    }, [
        props.placement
    ]);
    if (ref === activeToastContainer && state.visibleToasts.length > 0) return /*#__PURE__*/ (0, $90KaU$react).createElement((0, $74a94cdc2e765763$export$fb98e3a2a4cd92d7), {
        state: state,
        ...props
    }, /*#__PURE__*/ (0, $90KaU$react).createElement("ol", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($90KaU$toastContainer_cssmjs))), 'spectrum-ToastContainer-list')
    }, state.visibleToasts.map((toast, index)=>{
        let shouldFade = isCentered && index !== 0;
        return /*#__PURE__*/ (0, $90KaU$react).createElement("li", {
            key: toast.key,
            className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($90KaU$toastContainer_cssmjs))), 'spectrum-ToastContainer-listitem'),
            style: {
                viewTransitionName: toast.key,
                viewTransitionClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($90KaU$toastContainer_cssmjs))), 'toast', placement, {
                    fadeOnly: shouldFade
                })
            }
        }, /*#__PURE__*/ (0, $90KaU$react).createElement((0, $cfebc782ec37c3c6$export$8d8dc7d5f743331b), {
            toast: toast,
            state: state
        }));
    })));
    return null;
}
function $33add86cc52875f9$var$addToast(children, variant, options = {}) {
    // Dispatch a custom event so that toasts can be intercepted and re-targeted, e.g. when inside an iframe.
    if (typeof CustomEvent !== 'undefined' && typeof window !== 'undefined') {
        let event = new CustomEvent('react-spectrum-toast', {
            cancelable: true,
            bubbles: true,
            detail: {
                children: children,
                variant: variant,
                options: options
            }
        });
        let shouldContinue = window.dispatchEvent(event);
        if (!shouldContinue) return ()=>{};
    }
    let value = {
        children: children,
        variant: variant,
        actionLabel: options.actionLabel,
        onAction: options.onAction,
        shouldCloseOnAction: options.shouldCloseOnAction,
        ...(0, $90KaU$filterDOMProps)(options)
    };
    // Minimum time of 5s from https://spectrum.adobe.com/page/toast/#Auto-dismissible
    // Actionable toasts cannot be auto dismissed. That would fail WCAG SC 2.2.1.
    // It is debatable whether non-actionable toasts would also fail.
    let timeout = options.timeout && !options.onAction ? Math.max(options.timeout, 5000) : undefined;
    let queue = $33add86cc52875f9$var$getGlobalToastQueue();
    let key = queue.add(value, {
        timeout: timeout,
        onClose: options.onClose
    });
    return ()=>queue.close(key);
}
const $33add86cc52875f9$export$f1f8569633bbbec4 = {
    /** Queues a neutral toast. */ neutral (children, options = {}) {
        return $33add86cc52875f9$var$addToast(children, 'neutral', options);
    },
    /** Queues a positive toast. */ positive (children, options = {}) {
        return $33add86cc52875f9$var$addToast(children, 'positive', options);
    },
    /** Queues a negative toast. */ negative (children, options = {}) {
        return $33add86cc52875f9$var$addToast(children, 'negative', options);
    },
    /** Queues an informational toast. */ info (children, options = {}) {
        return $33add86cc52875f9$var$addToast(children, 'info', options);
    }
};


export {$33add86cc52875f9$export$320311f0e4ecb3ae as clearToastQueue, $33add86cc52875f9$export$f2815235e76a62b9 as ToastContainer, $33add86cc52875f9$export$f1f8569633bbbec4 as ToastQueue};
//# sourceMappingURL=ToastContainer.mjs.map
