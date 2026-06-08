import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {CollectionRendererContext as $263ab7fc0f95ccdb$export$4feb769f8ddf26c5, DefaultCollectionRenderer as $263ab7fc0f95ccdb$export$a164736487e3f0ae, usePersistedKeys as $263ab7fc0f95ccdb$export$90e00781bc59d8f9} from "./Collection.mjs";
import {SelectionIndicatorContext as $91fe5e721c7f36c1$export$c9549807523555e0} from "./SelectionIndicator.mjs";
import {SharedElementTransition as $792f28e438b9ad5f$export$758399f318e6385a} from "./SharedElementTransition.mjs";
import {useTabList as $4KeVI$useTabList, useTab as $4KeVI$useTab, useTabPanel as $4KeVI$useTabPanel} from "react-aria/useTabList";
import {Collection as $4KeVI$Collection} from "react-aria/Collection";
import {CollectionBuilder as $4KeVI$CollectionBuilder, createLeafComponent as $4KeVI$createLeafComponent} from "react-aria/CollectionBuilder";
import {CollectionNode as $4KeVI$CollectionNode} from "react-aria/private/collections/BaseCollection";
import {createHideableComponent as $4KeVI$createHideableComponent} from "react-aria/private/collections/Hidden";
import {filterDOMProps as $4KeVI$filterDOMProps} from "react-aria/filterDOMProps";
import {inertValue as $4KeVI$inertValue} from "react-aria/private/utils/inertValue";
import {mergeProps as $4KeVI$mergeProps} from "react-aria/mergeProps";
import $4KeVI$react, {createContext as $4KeVI$createContext, forwardRef as $4KeVI$forwardRef, useMemo as $4KeVI$useMemo, useContext as $4KeVI$useContext, useRef as $4KeVI$useRef, useState as $4KeVI$useState} from "react";
import {useTabListState as $4KeVI$useTabListState} from "react-stately/useTabListState";
import {useExitAnimation as $4KeVI$useExitAnimation, useEnterAnimation as $4KeVI$useEnterAnimation} from "react-aria/private/utils/animation";
import {useFocusRing as $4KeVI$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $4KeVI$useHover} from "react-aria/useHover";
import {useLayoutEffect as $4KeVI$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useObjectRef as $4KeVI$useObjectRef} from "react-aria/useObjectRef";

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


















const $b4f18e3395fe64d7$export$cfa7aa87c26e7d1f = /*#__PURE__*/ (0, $4KeVI$createContext)(null);
const $b4f18e3395fe64d7$export$364712098d2aa57c = /*#__PURE__*/ (0, $4KeVI$createContext)(null);
const $b4f18e3395fe64d7$export$b2539bed5023c21c = /*#__PURE__*/ (0, $4KeVI$forwardRef)(function Tabs(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $b4f18e3395fe64d7$export$cfa7aa87c26e7d1f);
    let { children: children, orientation: orientation = 'horizontal' } = props;
    children = (0, $4KeVI$useMemo)(()=>typeof children === 'function' ? children({
            orientation: orientation,
            defaultChildren: null
        }) : children, [
        children,
        orientation
    ]);
    return /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $4KeVI$CollectionBuilder), {
        content: children
    }, (collection)=>/*#__PURE__*/ (0, $4KeVI$react).createElement($b4f18e3395fe64d7$var$TabsInner, {
            props: props,
            collection: collection,
            tabsRef: ref
        }));
});
function $b4f18e3395fe64d7$var$TabsInner({ props: props, tabsRef: ref, collection: collection }) {
    let { orientation: orientation = 'horizontal' } = props;
    let state = (0, $4KeVI$useTabListState)({
        ...props,
        collection: collection,
        children: undefined
    });
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $4KeVI$useFocusRing)({
        within: true
    });
    let values = (0, $4KeVI$useMemo)(()=>({
            orientation: orientation,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible
        }), [
        orientation,
        isFocused,
        isFocusVisible
    ]);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-Tabs',
        values: values
    });
    let DOMProps = (0, $4KeVI$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $4KeVI$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": isFocused || undefined,
        "data-orientation": orientation,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": state.isDisabled || undefined
    }, /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $b4f18e3395fe64d7$export$cfa7aa87c26e7d1f,
                props
            ],
            [
                $b4f18e3395fe64d7$export$364712098d2aa57c,
                state
            ]
        ]
    }, renderProps.children));
}
const $b4f18e3395fe64d7$export$e51a686c67fdaa2d = /*#__PURE__*/ (0, $4KeVI$forwardRef)(function TabList(props, ref) {
    let state = (0, $4KeVI$useContext)($b4f18e3395fe64d7$export$364712098d2aa57c);
    return state ? /*#__PURE__*/ (0, $4KeVI$react).createElement($b4f18e3395fe64d7$var$TabListInner, {
        props: props,
        forwardedRef: ref
    }) : /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $4KeVI$Collection), props);
});
function $b4f18e3395fe64d7$var$TabListInner({ props: props, forwardedRef: ref }) {
    let state = (0, $4KeVI$useContext)($b4f18e3395fe64d7$export$364712098d2aa57c);
    let { CollectionRoot: CollectionRoot } = (0, $4KeVI$useContext)((0, $263ab7fc0f95ccdb$export$4feb769f8ddf26c5));
    let { orientation: orientation = 'horizontal', keyboardActivation: keyboardActivation = 'automatic' } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($b4f18e3395fe64d7$export$cfa7aa87c26e7d1f);
    let objectRef = (0, $4KeVI$useObjectRef)(ref);
    let { tabListProps: tabListProps } = (0, $4KeVI$useTabList)({
        ...props,
        orientation: orientation,
        keyboardActivation: keyboardActivation
    }, state, objectRef);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        children: null,
        defaultClassName: 'react-aria-TabList',
        values: {
            orientation: orientation,
            state: state
        }
    });
    let DOMProps = (0, $4KeVI$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $4KeVI$mergeProps)(DOMProps, renderProps, tabListProps),
        ref: objectRef,
        "data-orientation": orientation || undefined
    }, /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $792f28e438b9ad5f$export$758399f318e6385a), null, /*#__PURE__*/ (0, $4KeVI$react).createElement(CollectionRoot, {
        collection: state.collection,
        persistedKeys: (0, $263ab7fc0f95ccdb$export$90e00781bc59d8f9)(state.selectionManager.focusedKey)
    })));
}
class $b4f18e3395fe64d7$var$TabItemNode extends (0, $4KeVI$CollectionNode) {
    static{
        this.type = 'item';
    }
}
const $b4f18e3395fe64d7$export$3e41faf802a29e71 = /*#__PURE__*/ (0, $4KeVI$createLeafComponent)($b4f18e3395fe64d7$var$TabItemNode, (props, forwardedRef, item)=>{
    let state = (0, $4KeVI$useContext)($b4f18e3395fe64d7$export$364712098d2aa57c);
    let ref = (0, $4KeVI$useObjectRef)(forwardedRef);
    let { tabProps: tabProps, isSelected: isSelected, isDisabled: isDisabled, isPressed: isPressed } = (0, $4KeVI$useTab)({
        key: item.key,
        ...props
    }, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $4KeVI$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $4KeVI$useHover)({
        isDisabled: isDisabled,
        onHoverStart: props.onHoverStart,
        onHoverEnd: props.onHoverEnd,
        onHoverChange: props.onHoverChange
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        id: undefined,
        children: item.rendered,
        defaultClassName: 'react-aria-Tab',
        values: {
            isSelected: isSelected,
            isDisabled: isDisabled,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isPressed: isPressed,
            isHovered: isHovered
        }
    });
    let ElementType = item.props.href ? (0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).a : (0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div;
    let DOMProps = (0, $4KeVI$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, $4KeVI$react).createElement(ElementType, {
        ...(0, $4KeVI$mergeProps)(DOMProps, renderProps, tabProps, focusProps, hoverProps),
        ref: ref,
        "data-selected": isSelected || undefined,
        "data-disabled": isDisabled || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-pressed": isPressed || undefined,
        "data-hovered": isHovered || undefined
    }, /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $91fe5e721c7f36c1$export$c9549807523555e0).Provider, {
        value: {
            isSelected: isSelected
        }
    }, renderProps.children));
});
const $b4f18e3395fe64d7$export$5dae8d435677f210 = /*#__PURE__*/ (0, $4KeVI$createHideableComponent)(function TabPanels(props, forwardedRef) {
    let state = (0, $4KeVI$useContext)($b4f18e3395fe64d7$export$364712098d2aa57c);
    let ref = (0, $4KeVI$useObjectRef)(forwardedRef);
    let selectedKeyRef = (0, $4KeVI$useRef)(state.selectedKey);
    let prevSize = (0, $4KeVI$useRef)(null);
    let hasTransition = (0, $4KeVI$useRef)(null);
    (0, $4KeVI$useLayoutEffect)(()=>{
        let el = ref.current;
        if (!el) return;
        if (hasTransition.current == null) hasTransition.current = /width|height|block-size|inline-size|all/.test(window.getComputedStyle(el).transition);
        if (hasTransition.current && selectedKeyRef.current != null && selectedKeyRef.current !== state.selectedKey) {
            // Measure auto size.
            el.style.setProperty('--tab-panel-width', 'auto');
            el.style.setProperty('--tab-panel-height', 'auto');
            let { width: width, height: height } = el.getBoundingClientRect();
            if (prevSize.current && (prevSize.current.width !== width || prevSize.current.height !== height)) {
                // Revert to previous size.
                el.style.setProperty('--tab-panel-width', prevSize.current.width + 'px');
                el.style.setProperty('--tab-panel-height', prevSize.current.height + 'px');
                // Force style re-calculation to trigger animations.
                window.getComputedStyle(el).height;
                // Animate to current pixel size.
                el.style.setProperty('--tab-panel-width', width + 'px');
                el.style.setProperty('--tab-panel-height', height + 'px');
                // When animations complete, revert back to auto size.
                Promise.all(el.getAnimations().map((a)=>a.finished)).then(()=>{
                    el.style.setProperty('--tab-panel-width', 'auto');
                    el.style.setProperty('--tab-panel-height', 'auto');
                }).catch(()=>{});
            }
        }
        selectedKeyRef.current = state.selectedKey;
    }, [
        ref,
        state.selectedKey
    ]);
    // Store previous size before DOM updates occur.
    // This breaks the rules of hooks because there is no effect that runs _before_ DOM updates.
    if (state.selectedKey != null && // eslint-disable-next-line rsp-rules/pure-render
    state.selectedKey !== selectedKeyRef.current && ref.current && // eslint-disable-next-line rsp-rules/pure-render
    hasTransition.current) // eslint-disable-next-line rsp-rules/pure-render
    prevSize.current = ref.current.getBoundingClientRect();
    let DOMProps = (0, $4KeVI$filterDOMProps)(props, {
        labelable: true,
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        render: props.render,
        ...DOMProps,
        ref: ref,
        style: props.style,
        className: props.className || 'react-aria-TabPanels'
    }, /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $4KeVI$Collection), props));
});
const $b4f18e3395fe64d7$export$3d96ec278d3efce4 = /*#__PURE__*/ (0, $4KeVI$createHideableComponent)(function TabPanel(props, forwardedRef) {
    const state = (0, $4KeVI$useContext)($b4f18e3395fe64d7$export$364712098d2aa57c);
    let ref = (0, $4KeVI$useObjectRef)(forwardedRef);
    // Track if the tab panel was initially selected on mount (after extra render to populate the collection).
    // In this case, we don't want to trigger animations.
    let isSelected = state.selectedKey === props.id;
    let [isInitiallySelected, setInitiallySelected] = (0, $4KeVI$useState)(state.selectedKey != null ? isSelected : null);
    if (isInitiallySelected == null && state.selectedKey != null) setInitiallySelected(isSelected);
    else if (!isSelected && isInitiallySelected) setInitiallySelected(false);
    let isExiting = (0, $4KeVI$useExitAnimation)(ref, isSelected);
    if (!isSelected && !props.shouldForceMount && !isExiting) return null;
    return /*#__PURE__*/ (0, $4KeVI$react).createElement($b4f18e3395fe64d7$var$TabPanelInner, {
        ...props,
        tabPanelRef: ref,
        isInitiallySelected: isInitiallySelected || false,
        isExiting: isExiting
    });
});
function $b4f18e3395fe64d7$var$TabPanelInner(props) {
    let state = (0, $4KeVI$useContext)($b4f18e3395fe64d7$export$364712098d2aa57c);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { id: id, tabPanelRef: ref, isInitiallySelected: isInitiallySelected, isExiting: isExiting, ...otherProps } = props;
    let { tabPanelProps: tabPanelProps } = (0, $4KeVI$useTabPanel)(props, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $4KeVI$useFocusRing)();
    let isSelected = state.selectedKey === props.id;
    let isEntering = (0, $4KeVI$useEnterAnimation)(ref) && !isInitiallySelected;
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-TabPanel',
        values: {
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            // @ts-ignore - compatibility with React < 19
            isInert: (0, $4KeVI$inertValue)(!isSelected),
            isEntering: isEntering,
            isExiting: isExiting,
            state: state
        }
    });
    let DOMProps = (0, $4KeVI$filterDOMProps)(otherProps, {
        global: true
    });
    delete DOMProps.id;
    let domProps = isSelected ? (0, $4KeVI$mergeProps)(DOMProps, tabPanelProps, focusProps, renderProps) : (0, $4KeVI$mergeProps)(DOMProps, renderProps);
    return /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...domProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        // @ts-ignore
        inert: (0, $4KeVI$inertValue)(!isSelected || props.inert),
        "data-inert": !isSelected ? 'true' : undefined,
        "data-entering": isEntering || undefined,
        "data-exiting": isExiting || undefined
    }, /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $b4f18e3395fe64d7$export$cfa7aa87c26e7d1f,
                null
            ],
            [
                $b4f18e3395fe64d7$export$364712098d2aa57c,
                null
            ]
        ]
    }, /*#__PURE__*/ (0, $4KeVI$react).createElement((0, $263ab7fc0f95ccdb$export$4feb769f8ddf26c5).Provider, {
        value: (0, $263ab7fc0f95ccdb$export$a164736487e3f0ae)
    }, renderProps.children)));
}


export {$b4f18e3395fe64d7$export$cfa7aa87c26e7d1f as TabsContext, $b4f18e3395fe64d7$export$364712098d2aa57c as TabListStateContext, $b4f18e3395fe64d7$export$b2539bed5023c21c as Tabs, $b4f18e3395fe64d7$export$e51a686c67fdaa2d as TabList, $b4f18e3395fe64d7$export$3e41faf802a29e71 as Tab, $b4f18e3395fe64d7$export$5dae8d435677f210 as TabPanels, $b4f18e3395fe64d7$export$3d96ec278d3efce4 as TabPanel};
//# sourceMappingURL=Tabs.mjs.map
