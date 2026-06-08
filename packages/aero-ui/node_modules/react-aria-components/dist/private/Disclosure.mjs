import {ButtonContext as $7705c033048f6da7$export$24d547caef80ccd1} from "./Button.mjs";
import {DEFAULT_SLOT as $7230ffa83bc0c2cf$export$c62b8e45d58ddad9, dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {useDisclosure as $evqUs$useDisclosure} from "react-aria/useDisclosure";
import {useDisclosureGroupState as $evqUs$useDisclosureGroupState} from "react-stately/useDisclosureGroupState";
import {useDisclosureState as $evqUs$useDisclosureState} from "react-stately/useDisclosureState";
import {filterDOMProps as $evqUs$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $evqUs$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $evqUs$mergeRefs} from "react-aria/mergeRefs";
import $evqUs$react, {createContext as $evqUs$createContext, forwardRef as $evqUs$forwardRef, useContext as $evqUs$useContext} from "react";
import {useFocusRing as $evqUs$useFocusRing} from "react-aria/useFocusRing";
import {useId as $evqUs$useId} from "react-aria/useId";

/*
 * Copyright 2024 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 










const $a3e16eab0ea4036c$export$1d40e3e0cc4d5de = /*#__PURE__*/ (0, $evqUs$createContext)(null);
const $a3e16eab0ea4036c$export$944aceb4f8c89f10 = /*#__PURE__*/ (0, $evqUs$forwardRef)(function DisclosureGroup(props, ref) {
    let state = (0, $evqUs$useDisclosureGroupState)(props);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-DisclosureGroup',
        values: {
            isDisabled: state.isDisabled,
            state: state
        }
    });
    let domProps = (0, $evqUs$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $evqUs$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...domProps,
        ...renderProps,
        ref: ref,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $evqUs$react).createElement($a3e16eab0ea4036c$export$1d40e3e0cc4d5de.Provider, {
        value: state
    }, renderProps.children));
});
const $a3e16eab0ea4036c$export$d665dd135a51b28a = /*#__PURE__*/ (0, $evqUs$createContext)(null);
const $a3e16eab0ea4036c$export$dab3ea4a6ef094da = /*#__PURE__*/ (0, $evqUs$createContext)(null);
const $a3e16eab0ea4036c$var$InternalDisclosureContext = /*#__PURE__*/ (0, $evqUs$createContext)(null);
const $a3e16eab0ea4036c$export$74a362b31437ec83 = /*#__PURE__*/ (0, $evqUs$forwardRef)(function Disclosure(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $a3e16eab0ea4036c$export$d665dd135a51b28a);
    let groupState = (0, $evqUs$useContext)($a3e16eab0ea4036c$export$1d40e3e0cc4d5de);
    let { id: id, ...otherProps } = props;
    // Generate an id if one wasn't provided.
    // (can't pass id into useId since it can also be a number)
    let defaultId = (0, $evqUs$useId)();
    id ||= defaultId;
    let isExpanded = groupState ? groupState.expandedKeys.has(id) : props.isExpanded;
    let state = (0, $evqUs$useDisclosureState)({
        ...props,
        isExpanded: isExpanded,
        onExpandedChange (isExpanded) {
            if (groupState) groupState.toggleKey(id);
            props.onExpandedChange?.(isExpanded);
        }
    });
    let panelRef = (0, $evqUs$react).useRef(null);
    let isDisabled = props.isDisabled || groupState?.isDisabled || false;
    let { buttonProps: buttonProps, panelProps: panelProps } = (0, $evqUs$useDisclosure)({
        ...props,
        isExpanded: isExpanded,
        isDisabled: isDisabled
    }, state, panelRef);
    let { isFocusVisible: isFocusVisibleWithin, focusProps: focusWithinProps } = (0, $evqUs$useFocusRing)({
        within: true
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        id: undefined,
        defaultClassName: 'react-aria-Disclosure',
        values: {
            isExpanded: state.isExpanded,
            isDisabled: isDisabled,
            isFocusVisibleWithin: isFocusVisibleWithin,
            state: state
        }
    });
    let domProps = (0, $evqUs$filterDOMProps)(otherProps, {
        global: true
    });
    return /*#__PURE__*/ (0, $evqUs$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    slots: {
                        [(0, $7230ffa83bc0c2cf$export$c62b8e45d58ddad9)]: {},
                        trigger: buttonProps
                    }
                }
            ],
            [
                $a3e16eab0ea4036c$var$InternalDisclosureContext,
                {
                    panelProps: panelProps,
                    panelRef: panelRef
                }
            ],
            [
                $a3e16eab0ea4036c$export$dab3ea4a6ef094da,
                state
            ]
        ]
    }, /*#__PURE__*/ (0, $evqUs$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $evqUs$mergeProps)(domProps, renderProps, focusWithinProps),
        ref: ref,
        "data-expanded": state.isExpanded || undefined,
        "data-disabled": isDisabled || undefined,
        "data-focus-visible-within": isFocusVisibleWithin || undefined
    }, renderProps.children));
});
const $a3e16eab0ea4036c$export$feabaa331e1d464c = /*#__PURE__*/ (0, $evqUs$forwardRef)(function DisclosurePanel(props, ref) {
    let { role: role = 'group' } = props;
    let { panelProps: panelProps, panelRef: panelRef } = (0, $evqUs$useContext)($a3e16eab0ea4036c$var$InternalDisclosureContext);
    let { isFocusVisible: isFocusVisibleWithin, focusProps: focusWithinProps } = (0, $evqUs$useFocusRing)({
        within: true
    });
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-DisclosurePanel',
        values: {
            isFocusVisibleWithin: isFocusVisibleWithin
        }
    });
    let DOMProps = (0, $evqUs$filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    return /*#__PURE__*/ (0, $evqUs$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $evqUs$mergeProps)(DOMProps, renderProps, panelProps, focusWithinProps),
        ref: (0, $evqUs$mergeRefs)(ref, panelRef),
        role: role,
        "data-focus-visible-within": isFocusVisibleWithin || undefined
    }, /*#__PURE__*/ (0, $evqUs$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                null
            ]
        ]
    }, props.children));
});


export {$a3e16eab0ea4036c$export$1d40e3e0cc4d5de as DisclosureGroupStateContext, $a3e16eab0ea4036c$export$944aceb4f8c89f10 as DisclosureGroup, $a3e16eab0ea4036c$export$d665dd135a51b28a as DisclosureContext, $a3e16eab0ea4036c$export$dab3ea4a6ef094da as DisclosureStateContext, $a3e16eab0ea4036c$export$74a362b31437ec83 as Disclosure, $a3e16eab0ea4036c$export$feabaa331e1d464c as DisclosurePanel};
//# sourceMappingURL=Disclosure.mjs.map
