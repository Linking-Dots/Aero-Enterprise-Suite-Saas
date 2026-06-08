import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {flushSync as $3Lq00$flushSync} from "react-dom";
import $3Lq00$react, {createContext as $3Lq00$createContext, useRef as $3Lq00$useRef, forwardRef as $3Lq00$forwardRef, useState as $3Lq00$useState, useContext as $3Lq00$useContext} from "react";
import {useLayoutEffect as $3Lq00$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useObjectRef as $3Lq00$useObjectRef} from "react-aria/useObjectRef";

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




const $792f28e438b9ad5f$var$SharedElementContext = /*#__PURE__*/ (0, $3Lq00$createContext)(null);
function $792f28e438b9ad5f$export$758399f318e6385a(props) {
    let ref = (0, $3Lq00$useRef)({});
    return /*#__PURE__*/ (0, $3Lq00$react).createElement($792f28e438b9ad5f$var$SharedElementContext.Provider, {
        value: ref
    }, props.children);
}
const $792f28e438b9ad5f$export$c34620ff8881d89f = /*#__PURE__*/ (0, $3Lq00$forwardRef)(function SharedElement(props, ref) {
    let { name: name, isVisible: isVisible = true, children: children, className: className, style: style, render: render, ...divProps } = props;
    let [state, setState] = (0, $3Lq00$useState)(isVisible ? 'visible' : 'hidden');
    let scopeRef = (0, $3Lq00$useContext)($792f28e438b9ad5f$var$SharedElementContext);
    if (!scopeRef) throw new Error('<SharedElement> must be rendered inside a <SharedElementTransition>');
    if (isVisible && state === 'hidden') setState('visible');
    ref = (0, $3Lq00$useObjectRef)(ref);
    (0, $3Lq00$useLayoutEffect)(()=>{
        let element = ref.current;
        let scope = scopeRef.current;
        let prevSnapshot = scope[name];
        let frame = null;
        if (element && isVisible && prevSnapshot) {
            // Element is transitioning from a previous instance.
            setState('visible');
            let animations = element.getAnimations();
            // Set properties to animate from.
            let values = prevSnapshot.style.map(([property, prevValue])=>{
                let value = element.style[property];
                if (property === 'translate') {
                    let prevRect = prevSnapshot.rect;
                    let currentItem = element.getBoundingClientRect();
                    let deltaX = prevRect.left - currentItem?.left;
                    let deltaY = prevRect.top - currentItem?.top;
                    element.style.translate = `${deltaX}px ${deltaY}px`;
                } else element.style[property] = prevValue;
                return [
                    property,
                    value
                ];
            });
            // Cancel any new animations triggered by these properties.
            for (let a of element.getAnimations())if (!animations.includes(a)) a.cancel();
            // Remove overrides after one frame to animate to the current values.
            frame = requestAnimationFrame(()=>{
                frame = null;
                for (let [property, value] of values)element.style[property] = value;
            });
            delete scope[name];
        } else if (element && isVisible && !prevSnapshot) {
            // No previous instance exists, apply the entering state.
            queueMicrotask(()=>(0, $3Lq00$flushSync)(()=>setState('entering')));
            frame = requestAnimationFrame(()=>{
                frame = null;
                setState('visible');
            });
        } else if (element && !isVisible) // Wait until layout effects finish, and check if a snapshot still exists.
        // If so, no new SharedElement consumed it, so enter the exiting state.
        queueMicrotask(()=>{
            if (scope[name]) {
                delete scope[name];
                (0, $3Lq00$flushSync)(()=>setState('exiting'));
                Promise.all(element.getAnimations().map((a)=>a.finished)).then(()=>setState('hidden')).catch(()=>{});
            } else // Snapshot was consumed by another instance, unmount.
            setState('hidden');
        });
        return ()=>{
            if (frame != null) cancelAnimationFrame(frame);
            if (element && element.isConnected && !element.hasAttribute('data-exiting')) {
                // On unmount, store a snapshot of the rectangle and computed style for transitioning properties.
                let style = window.getComputedStyle(element);
                if (style.transitionProperty !== 'none') {
                    let transitionProperty = style.transitionProperty.split(/\s*,\s*/);
                    scope[name] = {
                        rect: element.getBoundingClientRect(),
                        style: transitionProperty.map((p)=>[
                                p,
                                style[p]
                            ])
                    };
                }
            }
        };
    }, [
        ref,
        scopeRef,
        name,
        isVisible
    ]);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        children: children,
        className: className,
        style: style,
        render: render,
        values: {
            isEntering: state === 'entering',
            isExiting: state === 'exiting'
        }
    });
    if (state === 'hidden') return null;
    return /*#__PURE__*/ (0, $3Lq00$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...divProps,
        ...renderProps,
        ref: ref,
        "data-entering": state === 'entering' || undefined,
        "data-exiting": state === 'exiting' || undefined
    });
});


export {$792f28e438b9ad5f$export$758399f318e6385a as SharedElementTransition, $792f28e438b9ad5f$export$c34620ff8881d89f as SharedElement};
//# sourceMappingURL=SharedElementTransition.mjs.map
