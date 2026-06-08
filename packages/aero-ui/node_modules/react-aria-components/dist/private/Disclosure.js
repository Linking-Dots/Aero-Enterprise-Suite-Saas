import {ButtonContext as $fc203795b9b363cd$export$24d547caef80ccd1} from "./Button.js";
import {DEFAULT_SLOT as $b7b7a92703138c9b$export$c62b8e45d58ddad9, dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {useDisclosure as $hWhQn$useDisclosure} from "react-aria/useDisclosure";
import {useDisclosureGroupState as $hWhQn$useDisclosureGroupState} from "react-stately/useDisclosureGroupState";
import {useDisclosureState as $hWhQn$useDisclosureState} from "react-stately/useDisclosureState";
import {filterDOMProps as $hWhQn$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $hWhQn$mergeProps} from "react-aria/mergeProps";
import {mergeRefs as $hWhQn$mergeRefs} from "react-aria/mergeRefs";
import $hWhQn$react, {createContext as $hWhQn$createContext, forwardRef as $hWhQn$forwardRef, useContext as $hWhQn$useContext} from "react";
import {useFocusRing as $hWhQn$useFocusRing} from "react-aria/useFocusRing";
import {useId as $hWhQn$useId} from "react-aria/useId";

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










const $9322d6c8018c24e4$export$1d40e3e0cc4d5de = /*#__PURE__*/ (0, $hWhQn$createContext)(null);
const $9322d6c8018c24e4$export$944aceb4f8c89f10 = /*#__PURE__*/ (0, $hWhQn$forwardRef)(function DisclosureGroup(props, ref) {
    let state = (0, $hWhQn$useDisclosureGroupState)(props);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-DisclosureGroup',
        values: {
            isDisabled: state.isDisabled,
            state: state
        }
    });
    let domProps = (0, $hWhQn$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $hWhQn$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...domProps,
        ...renderProps,
        ref: ref,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $hWhQn$react).createElement($9322d6c8018c24e4$export$1d40e3e0cc4d5de.Provider, {
        value: state
    }, renderProps.children));
});
const $9322d6c8018c24e4$export$d665dd135a51b28a = /*#__PURE__*/ (0, $hWhQn$createContext)(null);
const $9322d6c8018c24e4$export$dab3ea4a6ef094da = /*#__PURE__*/ (0, $hWhQn$createContext)(null);
const $9322d6c8018c24e4$var$InternalDisclosureContext = /*#__PURE__*/ (0, $hWhQn$createContext)(null);
const $9322d6c8018c24e4$export$74a362b31437ec83 = /*#__PURE__*/ (0, $hWhQn$forwardRef)(function Disclosure(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $9322d6c8018c24e4$export$d665dd135a51b28a);
    let groupState = (0, $hWhQn$useContext)($9322d6c8018c24e4$export$1d40e3e0cc4d5de);
    let { id: id, ...otherProps } = props;
    // Generate an id if one wasn't provided.
    // (can't pass id into useId since it can also be a number)
    let defaultId = (0, $hWhQn$useId)();
    id || (id = defaultId);
    let isExpanded = groupState ? groupState.expandedKeys.has(id) : props.isExpanded;
    let state = (0, $hWhQn$useDisclosureState)({
        ...props,
        isExpanded: isExpanded,
        onExpandedChange (isExpanded) {
            var _props_onExpandedChange;
            if (groupState) groupState.toggleKey(id);
            (_props_onExpandedChange = props.onExpandedChange) === null || _props_onExpandedChange === void 0 ? void 0 : _props_onExpandedChange.call(props, isExpanded);
        }
    });
    let panelRef = (0, $hWhQn$react).useRef(null);
    let isDisabled = props.isDisabled || (groupState === null || groupState === void 0 ? void 0 : groupState.isDisabled) || false;
    let { buttonProps: buttonProps, panelProps: panelProps } = (0, $hWhQn$useDisclosure)({
        ...props,
        isExpanded: isExpanded,
        isDisabled: isDisabled
    }, state, panelRef);
    let { isFocusVisible: isFocusVisibleWithin, focusProps: focusWithinProps } = (0, $hWhQn$useFocusRing)({
        within: true
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let domProps = (0, $hWhQn$filterDOMProps)(otherProps, {
        global: true
    });
    return /*#__PURE__*/ (0, $hWhQn$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    slots: {
                        [(0, $b7b7a92703138c9b$export$c62b8e45d58ddad9)]: {},
                        trigger: buttonProps
                    }
                }
            ],
            [
                $9322d6c8018c24e4$var$InternalDisclosureContext,
                {
                    panelProps: panelProps,
                    panelRef: panelRef
                }
            ],
            [
                $9322d6c8018c24e4$export$dab3ea4a6ef094da,
                state
            ]
        ]
    }, /*#__PURE__*/ (0, $hWhQn$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $hWhQn$mergeProps)(domProps, renderProps, focusWithinProps),
        ref: ref,
        "data-expanded": state.isExpanded || undefined,
        "data-disabled": isDisabled || undefined,
        "data-focus-visible-within": isFocusVisibleWithin || undefined
    }, renderProps.children));
});
const $9322d6c8018c24e4$export$feabaa331e1d464c = /*#__PURE__*/ (0, $hWhQn$forwardRef)(function DisclosurePanel(props, ref) {
    let { role: role = 'group' } = props;
    let { panelProps: panelProps, panelRef: panelRef } = (0, $hWhQn$useContext)($9322d6c8018c24e4$var$InternalDisclosureContext);
    let { isFocusVisible: isFocusVisibleWithin, focusProps: focusWithinProps } = (0, $hWhQn$useFocusRing)({
        within: true
    });
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-DisclosurePanel',
        values: {
            isFocusVisibleWithin: isFocusVisibleWithin
        }
    });
    let DOMProps = (0, $hWhQn$filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    return /*#__PURE__*/ (0, $hWhQn$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $hWhQn$mergeProps)(DOMProps, renderProps, panelProps, focusWithinProps),
        ref: (0, $hWhQn$mergeRefs)(ref, panelRef),
        role: role,
        "data-focus-visible-within": isFocusVisibleWithin || undefined
    }, /*#__PURE__*/ (0, $hWhQn$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                null
            ]
        ]
    }, props.children));
});


export {$9322d6c8018c24e4$export$1d40e3e0cc4d5de as DisclosureGroupStateContext, $9322d6c8018c24e4$export$944aceb4f8c89f10 as DisclosureGroup, $9322d6c8018c24e4$export$d665dd135a51b28a as DisclosureContext, $9322d6c8018c24e4$export$dab3ea4a6ef094da as DisclosureStateContext, $9322d6c8018c24e4$export$74a362b31437ec83 as Disclosure, $9322d6c8018c24e4$export$feabaa331e1d464c as DisclosurePanel};
//# sourceMappingURL=Disclosure.js.map
