var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $ca4ccf35c6998262$exports = require("./Toast.cjs");
require("./toastContainer.css");
var $1e451ff201076fe2$exports = require("./toastContainer_css.cjs");
var $d20d60b56e209593$exports = require("./Toaster.cjs");
var $hBxo0$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $hBxo0$reactdom = require("react-dom");
var $hBxo0$react = require("react");
var $hBxo0$reactstatelyuseToastState = require("react-stately/useToastState");
var $hBxo0$usesyncexternalstoreshimindexjs = require("use-sync-external-store/shim/index.js");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ToastContainer", function () { return $28c44db067882928$export$f2815235e76a62b9; });
$parcel$export(module.exports, "ToastQueue", function () { return $28c44db067882928$export$f1f8569633bbbec4; });
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








function $28c44db067882928$var$wrapInViewTransition(fn) {
    if ('startViewTransition' in document) document.startViewTransition(()=>{
        (0, $hBxo0$reactdom.flushSync)(fn);
    }).ready.catch(()=>{});
    else fn();
}
// There is a single global toast queue instance for the whole app, initialized lazily.
let $28c44db067882928$var$globalToastQueue = null;
function $28c44db067882928$var$getGlobalToastQueue() {
    if (!$28c44db067882928$var$globalToastQueue) $28c44db067882928$var$globalToastQueue = new (0, $hBxo0$reactstatelyuseToastState.ToastQueue)({
        maxVisibleToasts: Infinity,
        wrapUpdate: $28c44db067882928$var$wrapInViewTransition
    });
    return $28c44db067882928$var$globalToastQueue;
}
function $28c44db067882928$export$320311f0e4ecb3ae() {
    $28c44db067882928$var$globalToastQueue = null;
}
let $28c44db067882928$var$toastProviders = new Set();
let $28c44db067882928$var$subscriptions = new Set();
function $28c44db067882928$var$subscribe(fn) {
    $28c44db067882928$var$subscriptions.add(fn);
    return ()=>$28c44db067882928$var$subscriptions.delete(fn);
}
function $28c44db067882928$var$triggerSubscriptions() {
    for (let fn of $28c44db067882928$var$subscriptions)fn();
}
function $28c44db067882928$var$getActiveToastContainer() {
    return $28c44db067882928$var$toastProviders.values().next().value;
}
function $28c44db067882928$var$useActiveToastContainer() {
    return (0, $hBxo0$usesyncexternalstoreshimindexjs.useSyncExternalStore)($28c44db067882928$var$subscribe, $28c44db067882928$var$getActiveToastContainer, $28c44db067882928$var$getActiveToastContainer);
}
function $28c44db067882928$export$f2815235e76a62b9(props) {
    // Track all toast provider instances in a set.
    // Only the first one will actually render.
    // We use a ref to do this, since it will have a stable identity
    // over the lifetime of the component.
    let ref = (0, $hBxo0$react.useRef)(null);
    (0, $hBxo0$react.useEffect)(()=>{
        $28c44db067882928$var$toastProviders.add(ref);
        $28c44db067882928$var$triggerSubscriptions();
        return ()=>{
            // Remove this toast provider, and call subscriptions.
            // This will cause all other instances to re-render,
            // and the first one to become the new active toast provider.
            $28c44db067882928$var$toastProviders.delete(ref);
            $28c44db067882928$var$triggerSubscriptions();
        };
    }, []);
    // Only render if this is the active toast provider instance, and there are visible toasts.
    let activeToastContainer = $28c44db067882928$var$useActiveToastContainer();
    let state = (0, $hBxo0$reactstatelyuseToastState.useToastQueue)($28c44db067882928$var$getGlobalToastQueue());
    let { placement: placement, isCentered: isCentered } = (0, $hBxo0$react.useMemo)(()=>{
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
    if (ref === activeToastContainer && state.visibleToasts.length > 0) return /*#__PURE__*/ (0, ($parcel$interopDefault($hBxo0$react))).createElement((0, $d20d60b56e209593$exports.Toaster), {
        state: state,
        ...props
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hBxo0$react))).createElement("ol", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1e451ff201076fe2$exports))), 'spectrum-ToastContainer-list')
    }, state.visibleToasts.map((toast, index)=>{
        let shouldFade = isCentered && index !== 0;
        return /*#__PURE__*/ (0, ($parcel$interopDefault($hBxo0$react))).createElement("li", {
            key: toast.key,
            className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1e451ff201076fe2$exports))), 'spectrum-ToastContainer-listitem'),
            style: {
                viewTransitionName: toast.key,
                viewTransitionClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1e451ff201076fe2$exports))), 'toast', placement, {
                    fadeOnly: shouldFade
                })
            }
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($hBxo0$react))).createElement((0, $ca4ccf35c6998262$exports.Toast), {
            toast: toast,
            state: state
        }));
    })));
    return null;
}
function $28c44db067882928$var$addToast(children, variant, options = {}) {
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
        ...(0, $hBxo0$reactariafilterDOMProps.filterDOMProps)(options)
    };
    // Minimum time of 5s from https://spectrum.adobe.com/page/toast/#Auto-dismissible
    // Actionable toasts cannot be auto dismissed. That would fail WCAG SC 2.2.1.
    // It is debatable whether non-actionable toasts would also fail.
    let timeout = options.timeout && !options.onAction ? Math.max(options.timeout, 5000) : undefined;
    let queue = $28c44db067882928$var$getGlobalToastQueue();
    let key = queue.add(value, {
        timeout: timeout,
        onClose: options.onClose
    });
    return ()=>queue.close(key);
}
const $28c44db067882928$export$f1f8569633bbbec4 = {
    /** Queues a neutral toast. */ neutral (children, options = {}) {
        return $28c44db067882928$var$addToast(children, 'neutral', options);
    },
    /** Queues a positive toast. */ positive (children, options = {}) {
        return $28c44db067882928$var$addToast(children, 'positive', options);
    },
    /** Queues a negative toast. */ negative (children, options = {}) {
        return $28c44db067882928$var$addToast(children, 'negative', options);
    },
    /** Queues an informational toast. */ info (children, options = {}) {
        return $28c44db067882928$var$addToast(children, 'info', options);
    }
};


//# sourceMappingURL=ToastContainer.cjs.map
