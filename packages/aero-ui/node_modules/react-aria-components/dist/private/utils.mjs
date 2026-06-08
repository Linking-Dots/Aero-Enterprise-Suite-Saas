import {mergeProps as $ahdqC$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $ahdqC$mergeRefs} from "react-aria/mergeRefs";
import $ahdqC$react, {useMemo as $ahdqC$useMemo, useContext as $ahdqC$useContext, useState as $ahdqC$useState, useRef as $ahdqC$useRef, useCallback as $ahdqC$useCallback, forwardRef as $ahdqC$forwardRef} from "react";
import {useLayoutEffect as $ahdqC$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useObjectRef as $ahdqC$useObjectRef} from "react-aria/useObjectRef";

/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 




const $7230ffa83bc0c2cf$export$c62b8e45d58ddad9 = Symbol('default');
function $7230ffa83bc0c2cf$export$2881499e37b75b9a({ values: values, children: children }) {
    for (let [Context, value] of values)// @ts-ignore
    children = /*#__PURE__*/ (0, $ahdqC$react).createElement(Context.Provider, {
        value: value
    }, children);
    return children;
}
function $7230ffa83bc0c2cf$export$4d86445c2cf5e3(props) {
    let { className: className, style: style, children: children, defaultClassName: defaultClassName, defaultChildren: defaultChildren, defaultStyle: defaultStyle, values: values, render: render } = props;
    return (0, $ahdqC$useMemo)(()=>{
        let computedClassName;
        let computedStyle;
        let computedChildren;
        if (typeof className === 'function') computedClassName = className({
            ...values,
            defaultClassName: defaultClassName
        });
        else computedClassName = className;
        if (typeof style === 'function') computedStyle = style({
            ...values,
            defaultStyle: defaultStyle || {}
        });
        else computedStyle = style;
        if (typeof children === 'function') computedChildren = children({
            ...values,
            defaultChildren: defaultChildren
        });
        else if (children == null) computedChildren = defaultChildren;
        else computedChildren = children;
        return {
            className: computedClassName ?? defaultClassName,
            style: computedStyle || defaultStyle ? {
                ...defaultStyle,
                ...computedStyle
            } : undefined,
            children: computedChildren ?? defaultChildren,
            'data-rac': '',
            render: render ? (props)=>render(props, values) : undefined
        };
    }, [
        className,
        style,
        children,
        defaultClassName,
        defaultChildren,
        defaultStyle,
        values,
        render
    ]);
}
function $7230ffa83bc0c2cf$export$c245e6201fed2f75(// https://stackoverflow.com/questions/60898079/typescript-type-t-or-function-t-usage
value, wrap) {
    return (renderProps)=>wrap(typeof value === 'function' ? value(renderProps) : value, renderProps);
}
function $7230ffa83bc0c2cf$export$fabf2dc03a41866e(context, slot) {
    let ctx = (0, $ahdqC$useContext)(context);
    if (slot === null) // An explicit `null` slot means don't use context.
    return null;
    if (ctx && typeof ctx === 'object' && 'slots' in ctx && ctx.slots) {
        let slotKey = slot || $7230ffa83bc0c2cf$export$c62b8e45d58ddad9;
        if (!ctx.slots[slotKey]) {
            let availableSlots = new Intl.ListFormat().format(Object.keys(ctx.slots).map((p)=>`"${p}"`));
            let errorMessage = slot ? `Invalid slot "${slot}".` : 'A slot prop is required.';
            throw new Error(`${errorMessage} Valid slot names are ${availableSlots}.`);
        }
        return ctx.slots[slotKey];
    }
    // @ts-ignore
    return ctx;
}
function $7230ffa83bc0c2cf$export$29f1550f4b0d4415(props, ref, context) {
    let ctx = $7230ffa83bc0c2cf$export$fabf2dc03a41866e(context, props.slot) || {};
    let { ref: contextRef, ...contextProps } = ctx;
    let mergedRef = (0, $ahdqC$useObjectRef)((0, $ahdqC$useMemo)(()=>(0, $ahdqC$mergeRefs)(ref, contextRef), [
        ref,
        contextRef
    ]));
    let mergedProps = (0, $ahdqC$mergeProps)(contextProps, props);
    // mergeProps does not merge `style`. Adding this there might be a breaking change.
    if ('style' in contextProps && contextProps.style && 'style' in props && props.style) {
        if (typeof contextProps.style === 'function' || typeof props.style === 'function') // @ts-ignore
        mergedProps.style = (renderProps)=>{
            let contextStyle = typeof contextProps.style === 'function' ? contextProps.style(renderProps) : contextProps.style;
            let defaultStyle = {
                ...renderProps.defaultStyle,
                ...contextStyle
            };
            let style = typeof props.style === 'function' ? props.style({
                ...renderProps,
                defaultStyle: defaultStyle
            }) : props.style;
            return {
                ...defaultStyle,
                ...style
            };
        };
        else // @ts-ignore
        mergedProps.style = {
            ...contextProps.style,
            ...props.style
        };
    }
    return [
        mergedProps,
        mergedRef
    ];
}
function $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8(initialState = true) {
    // Initial state is typically based on the parent having an aria-label or aria-labelledby.
    // If it does, this value should be false so that we don't update the state and cause a rerender when we go through the layoutEffect
    let [hasSlot, setHasSlot] = (0, $ahdqC$useState)(initialState);
    let hasRun = (0, $ahdqC$useRef)(false);
    // A callback ref which will run when the slotted element mounts.
    // This should happen before the useLayoutEffect below.
    let ref = (0, $ahdqC$useCallback)((el)=>{
        hasRun.current = true;
        setHasSlot(!!el);
    }, []);
    // If the callback hasn't been called, then reset to false.
    (0, $ahdqC$useLayoutEffect)(()=>{
        if (!hasRun.current) setHasSlot(false);
    }, []);
    return [
        ref,
        hasSlot
    ];
}
function $7230ffa83bc0c2cf$export$ef03459518577ad4(props) {
    const prefix = /^(data-.*)$/;
    let filteredProps = {};
    for(const prop in props)if (!prefix.test(prop)) filteredProps[prop] = props[prop];
    return filteredProps;
}
function $7230ffa83bc0c2cf$var$DOMElement(ElementType, props, forwardedRef) {
    let { render: render, ...otherProps } = props;
    let elementRef = (0, $ahdqC$useRef)(null);
    let ref = (0, $ahdqC$useMemo)(()=>(0, $ahdqC$mergeRefs)(forwardedRef, elementRef), [
        forwardedRef,
        elementRef
    ]);
    (0, $ahdqC$useLayoutEffect)(()=>{
        if (process.env.NODE_ENV !== 'production' && render) {
            if (!elementRef.current) console.warn('Ref was not connected to DOM element returned by custom `render` function. Did you forget to pass through or merge the `ref`?');
            else if (elementRef.current.localName !== ElementType) console.warn(`Unexpected DOM element returned by custom \`render\` function. Expected <${ElementType}>, got <${elementRef.current.localName}>. This may break the component behavior and accessibility.`);
        }
    }, [
        ElementType,
        render
    ]);
    let domProps = {
        ...otherProps,
        ref: ref
    };
    if (render) return render(domProps, undefined);
    return /*#__PURE__*/ (0, $ahdqC$react).createElement(ElementType, domProps);
}
const $7230ffa83bc0c2cf$var$domComponentCache = {};
const $7230ffa83bc0c2cf$export$df3a06d6289f983e = new Proxy({}, {
    get (target, elementType) {
        if (typeof elementType !== 'string') return undefined;
        let res = $7230ffa83bc0c2cf$var$domComponentCache[elementType];
        if (!res) {
            res = /*#__PURE__*/ (0, $ahdqC$forwardRef)($7230ffa83bc0c2cf$var$DOMElement.bind(null, elementType));
            $7230ffa83bc0c2cf$var$domComponentCache[elementType] = res;
        }
        return res;
    }
});


export {$7230ffa83bc0c2cf$export$c62b8e45d58ddad9 as DEFAULT_SLOT, $7230ffa83bc0c2cf$export$2881499e37b75b9a as Provider, $7230ffa83bc0c2cf$export$4d86445c2cf5e3 as useRenderProps, $7230ffa83bc0c2cf$export$c245e6201fed2f75 as composeRenderProps, $7230ffa83bc0c2cf$export$fabf2dc03a41866e as useSlottedContext, $7230ffa83bc0c2cf$export$29f1550f4b0d4415 as useContextProps, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8 as useSlot, $7230ffa83bc0c2cf$export$ef03459518577ad4 as removeDataAttributes, $7230ffa83bc0c2cf$export$df3a06d6289f983e as dom};
//# sourceMappingURL=utils.mjs.map
