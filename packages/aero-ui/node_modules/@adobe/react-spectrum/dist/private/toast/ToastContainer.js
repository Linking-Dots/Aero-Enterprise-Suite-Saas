import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Toast as $5ecef0f995bde443$export$8d8dc7d5f743331b} from "./Toast.js";
import "./toastContainer.css";
import $4KPPu$toastContainer_cssmjs from "./toastContainer_css.mjs";
import {Toaster as $cf5b35630850d4e0$export$fb98e3a2a4cd92d7} from "./Toaster.js";
import {filterDOMProps as $4KPPu$filterDOMProps} from "react-aria/filterDOMProps";
import {flushSync as $4KPPu$flushSync} from "react-dom";
import $4KPPu$react, {useRef as $4KPPu$useRef, useEffect as $4KPPu$useEffect, useMemo as $4KPPu$useMemo} from "react";
import {ToastQueue as $4KPPu$ToastQueue, useToastQueue as $4KPPu$useToastQueue} from "react-stately/useToastState";
import {useSyncExternalStore as $4KPPu$useSyncExternalStore} from "use-sync-external-store/shim/index.js";


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








function $afd6f21ce00ee063$var$wrapInViewTransition(fn) {
    if ('startViewTransition' in document) document.startViewTransition(()=>{
        (0, $4KPPu$flushSync)(fn);
    }).ready.catch(()=>{});
    else fn();
}
// There is a single global toast queue instance for the whole app, initialized lazily.
let $afd6f21ce00ee063$var$globalToastQueue = null;
function $afd6f21ce00ee063$var$getGlobalToastQueue() {
    if (!$afd6f21ce00ee063$var$globalToastQueue) $afd6f21ce00ee063$var$globalToastQueue = new (0, $4KPPu$ToastQueue)({
        maxVisibleToasts: Infinity,
        wrapUpdate: $afd6f21ce00ee063$var$wrapInViewTransition
    });
    return $afd6f21ce00ee063$var$globalToastQueue;
}
function $afd6f21ce00ee063$export$320311f0e4ecb3ae() {
    $afd6f21ce00ee063$var$globalToastQueue = null;
}
let $afd6f21ce00ee063$var$toastProviders = new Set();
let $afd6f21ce00ee063$var$subscriptions = new Set();
function $afd6f21ce00ee063$var$subscribe(fn) {
    $afd6f21ce00ee063$var$subscriptions.add(fn);
    return ()=>$afd6f21ce00ee063$var$subscriptions.delete(fn);
}
function $afd6f21ce00ee063$var$triggerSubscriptions() {
    for (let fn of $afd6f21ce00ee063$var$subscriptions)fn();
}
function $afd6f21ce00ee063$var$getActiveToastContainer() {
    return $afd6f21ce00ee063$var$toastProviders.values().next().value;
}
function $afd6f21ce00ee063$var$useActiveToastContainer() {
    return (0, $4KPPu$useSyncExternalStore)($afd6f21ce00ee063$var$subscribe, $afd6f21ce00ee063$var$getActiveToastContainer, $afd6f21ce00ee063$var$getActiveToastContainer);
}
function $afd6f21ce00ee063$export$f2815235e76a62b9(props) {
    // Track all toast provider instances in a set.
    // Only the first one will actually render.
    // We use a ref to do this, since it will have a stable identity
    // over the lifetime of the component.
    let ref = (0, $4KPPu$useRef)(null);
    (0, $4KPPu$useEffect)(()=>{
        $afd6f21ce00ee063$var$toastProviders.add(ref);
        $afd6f21ce00ee063$var$triggerSubscriptions();
        return ()=>{
            // Remove this toast provider, and call subscriptions.
            // This will cause all other instances to re-render,
            // and the first one to become the new active toast provider.
            $afd6f21ce00ee063$var$toastProviders.delete(ref);
            $afd6f21ce00ee063$var$triggerSubscriptions();
        };
    }, []);
    // Only render if this is the active toast provider instance, and there are visible toasts.
    let activeToastContainer = $afd6f21ce00ee063$var$useActiveToastContainer();
    let state = (0, $4KPPu$useToastQueue)($afd6f21ce00ee063$var$getGlobalToastQueue());
    let { placement: placement, isCentered: isCentered } = (0, $4KPPu$useMemo)(()=>{
        var _props_placement;
        let placements = ((_props_placement = props.placement) !== null && _props_placement !== void 0 ? _props_placement : 'bottom').split(' ');
        let placement = placements[placements.length - 1];
        let isCentered = placements.length === 1;
        return {
            placement: placement,
            isCentered: isCentered
        };
    }, [
        props.placement
    ]);
    if (ref === activeToastContainer && state.visibleToasts.length > 0) return /*#__PURE__*/ (0, $4KPPu$react).createElement((0, $cf5b35630850d4e0$export$fb98e3a2a4cd92d7), {
        state: state,
        ...props
    }, /*#__PURE__*/ (0, $4KPPu$react).createElement("ol", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4KPPu$toastContainer_cssmjs))), 'spectrum-ToastContainer-list')
    }, state.visibleToasts.map((toast, index)=>{
        let shouldFade = isCentered && index !== 0;
        return /*#__PURE__*/ (0, $4KPPu$react).createElement("li", {
            key: toast.key,
            className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4KPPu$toastContainer_cssmjs))), 'spectrum-ToastContainer-listitem'),
            style: {
                viewTransitionName: toast.key,
                viewTransitionClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4KPPu$toastContainer_cssmjs))), 'toast', placement, {
                    fadeOnly: shouldFade
                })
            }
        }, /*#__PURE__*/ (0, $4KPPu$react).createElement((0, $5ecef0f995bde443$export$8d8dc7d5f743331b), {
            toast: toast,
            state: state
        }));
    })));
    return null;
}
function $afd6f21ce00ee063$var$addToast(children, variant, options = {}) {
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
        ...(0, $4KPPu$filterDOMProps)(options)
    };
    // Minimum time of 5s from https://spectrum.adobe.com/page/toast/#Auto-dismissible
    // Actionable toasts cannot be auto dismissed. That would fail WCAG SC 2.2.1.
    // It is debatable whether non-actionable toasts would also fail.
    let timeout = options.timeout && !options.onAction ? Math.max(options.timeout, 5000) : undefined;
    let queue = $afd6f21ce00ee063$var$getGlobalToastQueue();
    let key = queue.add(value, {
        timeout: timeout,
        onClose: options.onClose
    });
    return ()=>queue.close(key);
}
const $afd6f21ce00ee063$export$f1f8569633bbbec4 = {
    /** Queues a neutral toast. */ neutral (children, options = {}) {
        return $afd6f21ce00ee063$var$addToast(children, 'neutral', options);
    },
    /** Queues a positive toast. */ positive (children, options = {}) {
        return $afd6f21ce00ee063$var$addToast(children, 'positive', options);
    },
    /** Queues a negative toast. */ negative (children, options = {}) {
        return $afd6f21ce00ee063$var$addToast(children, 'negative', options);
    },
    /** Queues an informational toast. */ info (children, options = {}) {
        return $afd6f21ce00ee063$var$addToast(children, 'info', options);
    }
};


export {$afd6f21ce00ee063$export$320311f0e4ecb3ae as clearToastQueue, $afd6f21ce00ee063$export$f2815235e76a62b9 as ToastContainer, $afd6f21ce00ee063$export$f1f8569633bbbec4 as ToastQueue};
//# sourceMappingURL=ToastContainer.js.map
