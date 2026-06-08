import {mergeProps as $7ncfY$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $7ncfY$mergeRefs} from "react-aria/mergeRefs";
import $7ncfY$react, {useMemo as $7ncfY$useMemo, useContext as $7ncfY$useContext, useState as $7ncfY$useState, useRef as $7ncfY$useRef, useCallback as $7ncfY$useCallback, forwardRef as $7ncfY$forwardRef} from "react";
import {useLayoutEffect as $7ncfY$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useObjectRef as $7ncfY$useObjectRef} from "react-aria/useObjectRef";

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




const $b7b7a92703138c9b$export$c62b8e45d58ddad9 = Symbol('default');
function $b7b7a92703138c9b$export$2881499e37b75b9a({ values: values, children: children }) {
    for (let [Context, value] of values)// @ts-ignore
    children = /*#__PURE__*/ (0, $7ncfY$react).createElement(Context.Provider, {
        value: value
    }, children);
    return children;
}
function $b7b7a92703138c9b$export$4d86445c2cf5e3(props) {
    let { className: className, style: style, children: children, defaultClassName: defaultClassName, defaultChildren: defaultChildren, defaultStyle: defaultStyle, values: values, render: render } = props;
    return (0, $7ncfY$useMemo)(()=>{
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
            className: computedClassName !== null && computedClassName !== void 0 ? computedClassName : defaultClassName,
            style: computedStyle || defaultStyle ? {
                ...defaultStyle,
                ...computedStyle
            } : undefined,
            children: computedChildren !== null && computedChildren !== void 0 ? computedChildren : defaultChildren,
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
function $b7b7a92703138c9b$export$c245e6201fed2f75(// https://stackoverflow.com/questions/60898079/typescript-type-t-or-function-t-usage
value, wrap) {
    return (renderProps)=>wrap(typeof value === 'function' ? value(renderProps) : value, renderProps);
}
function $b7b7a92703138c9b$export$fabf2dc03a41866e(context, slot) {
    let ctx = (0, $7ncfY$useContext)(context);
    if (slot === null) // An explicit `null` slot means don't use context.
    return null;
    if (ctx && typeof ctx === 'object' && 'slots' in ctx && ctx.slots) {
        let slotKey = slot || $b7b7a92703138c9b$export$c62b8e45d58ddad9;
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
function $b7b7a92703138c9b$export$29f1550f4b0d4415(props, ref, context) {
    let ctx = $b7b7a92703138c9b$export$fabf2dc03a41866e(context, props.slot) || {};
    let { ref: contextRef, ...contextProps } = ctx;
    let mergedRef = (0, $7ncfY$useObjectRef)((0, $7ncfY$useMemo)(()=>(0, $7ncfY$mergeRefs)(ref, contextRef), [
        ref,
        contextRef
    ]));
    let mergedProps = (0, $7ncfY$mergeProps)(contextProps, props);
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
function $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8(initialState = true) {
    // Initial state is typically based on the parent having an aria-label or aria-labelledby.
    // If it does, this value should be false so that we don't update the state and cause a rerender when we go through the layoutEffect
    let [hasSlot, setHasSlot] = (0, $7ncfY$useState)(initialState);
    let hasRun = (0, $7ncfY$useRef)(false);
    // A callback ref which will run when the slotted element mounts.
    // This should happen before the useLayoutEffect below.
    let ref = (0, $7ncfY$useCallback)((el)=>{
        hasRun.current = true;
        setHasSlot(!!el);
    }, []);
    // If the callback hasn't been called, then reset to false.
    (0, $7ncfY$useLayoutEffect)(()=>{
        if (!hasRun.current) setHasSlot(false);
    }, []);
    return [
        ref,
        hasSlot
    ];
}
function $b7b7a92703138c9b$export$ef03459518577ad4(props) {
    const prefix = /^(data-.*)$/;
    let filteredProps = {};
    for(const prop in props)if (!prefix.test(prop)) filteredProps[prop] = props[prop];
    return filteredProps;
}
function $b7b7a92703138c9b$var$DOMElement(ElementType, props, forwardedRef) {
    let { render: render, ...otherProps } = props;
    let elementRef = (0, $7ncfY$useRef)(null);
    let ref = (0, $7ncfY$useMemo)(()=>(0, $7ncfY$mergeRefs)(forwardedRef, elementRef), [
        forwardedRef,
        elementRef
    ]);
    (0, $7ncfY$useLayoutEffect)(()=>{
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
    return /*#__PURE__*/ (0, $7ncfY$react).createElement(ElementType, domProps);
}
const $b7b7a92703138c9b$var$domComponentCache = {};
const $b7b7a92703138c9b$export$df3a06d6289f983e = new Proxy({}, {
    get (target, elementType) {
        if (typeof elementType !== 'string') return undefined;
        let res = $b7b7a92703138c9b$var$domComponentCache[elementType];
        if (!res) {
            res = /*#__PURE__*/ (0, $7ncfY$forwardRef)($b7b7a92703138c9b$var$DOMElement.bind(null, elementType));
            $b7b7a92703138c9b$var$domComponentCache[elementType] = res;
        }
        return res;
    }
});


export {$b7b7a92703138c9b$export$c62b8e45d58ddad9 as DEFAULT_SLOT, $b7b7a92703138c9b$export$2881499e37b75b9a as Provider, $b7b7a92703138c9b$export$4d86445c2cf5e3 as useRenderProps, $b7b7a92703138c9b$export$c245e6201fed2f75 as composeRenderProps, $b7b7a92703138c9b$export$fabf2dc03a41866e as useSlottedContext, $b7b7a92703138c9b$export$29f1550f4b0d4415 as useContextProps, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8 as useSlot, $b7b7a92703138c9b$export$ef03459518577ad4 as removeDataAttributes, $b7b7a92703138c9b$export$df3a06d6289f983e as dom};
//# sourceMappingURL=utils.js.map
