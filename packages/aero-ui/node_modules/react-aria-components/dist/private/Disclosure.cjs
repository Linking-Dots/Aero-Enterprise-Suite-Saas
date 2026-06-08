var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $8y5FN$reactariauseDisclosure = require("react-aria/useDisclosure");
var $8y5FN$reactstatelyuseDisclosureGroupState = require("react-stately/useDisclosureGroupState");
var $8y5FN$reactstatelyuseDisclosureState = require("react-stately/useDisclosureState");
var $8y5FN$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $8y5FN$reactariamergeProps = require("react-aria/mergeProps");
var $8y5FN$reactariamergeRefs = require("react-aria/mergeRefs");
var $8y5FN$react = require("react");
var $8y5FN$reactariauseFocusRing = require("react-aria/useFocusRing");
var $8y5FN$reactariauseId = require("react-aria/useId");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DisclosureGroupStateContext", function () { return $a8e1340dfdbe3a18$export$1d40e3e0cc4d5de; });
$parcel$export(module.exports, "DisclosureGroup", function () { return $a8e1340dfdbe3a18$export$944aceb4f8c89f10; });
$parcel$export(module.exports, "DisclosureContext", function () { return $a8e1340dfdbe3a18$export$d665dd135a51b28a; });
$parcel$export(module.exports, "DisclosureStateContext", function () { return $a8e1340dfdbe3a18$export$dab3ea4a6ef094da; });
$parcel$export(module.exports, "Disclosure", function () { return $a8e1340dfdbe3a18$export$74a362b31437ec83; });
$parcel$export(module.exports, "DisclosurePanel", function () { return $a8e1340dfdbe3a18$export$feabaa331e1d464c; });
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










const $a8e1340dfdbe3a18$export$1d40e3e0cc4d5de = /*#__PURE__*/ (0, $8y5FN$react.createContext)(null);
const $a8e1340dfdbe3a18$export$944aceb4f8c89f10 = /*#__PURE__*/ (0, $8y5FN$react.forwardRef)(function DisclosureGroup(props, ref) {
    let state = (0, $8y5FN$reactstatelyuseDisclosureGroupState.useDisclosureGroupState)(props);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-DisclosureGroup',
        values: {
            isDisabled: state.isDisabled,
            state: state
        }
    });
    let domProps = (0, $8y5FN$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8y5FN$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...domProps,
        ...renderProps,
        ref: ref,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8y5FN$react))).createElement($a8e1340dfdbe3a18$export$1d40e3e0cc4d5de.Provider, {
        value: state
    }, renderProps.children));
});
const $a8e1340dfdbe3a18$export$d665dd135a51b28a = /*#__PURE__*/ (0, $8y5FN$react.createContext)(null);
const $a8e1340dfdbe3a18$export$dab3ea4a6ef094da = /*#__PURE__*/ (0, $8y5FN$react.createContext)(null);
const $a8e1340dfdbe3a18$var$InternalDisclosureContext = /*#__PURE__*/ (0, $8y5FN$react.createContext)(null);
const $a8e1340dfdbe3a18$export$74a362b31437ec83 = /*#__PURE__*/ (0, $8y5FN$react.forwardRef)(function Disclosure(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $a8e1340dfdbe3a18$export$d665dd135a51b28a);
    let groupState = (0, $8y5FN$react.useContext)($a8e1340dfdbe3a18$export$1d40e3e0cc4d5de);
    let { id: id, ...otherProps } = props;
    // Generate an id if one wasn't provided.
    // (can't pass id into useId since it can also be a number)
    let defaultId = (0, $8y5FN$reactariauseId.useId)();
    id ||= defaultId;
    let isExpanded = groupState ? groupState.expandedKeys.has(id) : props.isExpanded;
    let state = (0, $8y5FN$reactstatelyuseDisclosureState.useDisclosureState)({
        ...props,
        isExpanded: isExpanded,
        onExpandedChange (isExpanded) {
            if (groupState) groupState.toggleKey(id);
            props.onExpandedChange?.(isExpanded);
        }
    });
    let panelRef = (0, ($parcel$interopDefault($8y5FN$react))).useRef(null);
    let isDisabled = props.isDisabled || groupState?.isDisabled || false;
    let { buttonProps: buttonProps, panelProps: panelProps } = (0, $8y5FN$reactariauseDisclosure.useDisclosure)({
        ...props,
        isExpanded: isExpanded,
        isDisabled: isDisabled
    }, state, panelRef);
    let { isFocusVisible: isFocusVisibleWithin, focusProps: focusWithinProps } = (0, $8y5FN$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let domProps = (0, $8y5FN$reactariafilterDOMProps.filterDOMProps)(otherProps, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8y5FN$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    slots: {
                        [(0, $048d76b84370f141$exports.DEFAULT_SLOT)]: {},
                        trigger: buttonProps
                    }
                }
            ],
            [
                $a8e1340dfdbe3a18$var$InternalDisclosureContext,
                {
                    panelProps: panelProps,
                    panelRef: panelRef
                }
            ],
            [
                $a8e1340dfdbe3a18$export$dab3ea4a6ef094da,
                state
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8y5FN$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $8y5FN$reactariamergeProps.mergeProps)(domProps, renderProps, focusWithinProps),
        ref: ref,
        "data-expanded": state.isExpanded || undefined,
        "data-disabled": isDisabled || undefined,
        "data-focus-visible-within": isFocusVisibleWithin || undefined
    }, renderProps.children));
});
const $a8e1340dfdbe3a18$export$feabaa331e1d464c = /*#__PURE__*/ (0, $8y5FN$react.forwardRef)(function DisclosurePanel(props, ref) {
    let { role: role = 'group' } = props;
    let { panelProps: panelProps, panelRef: panelRef } = (0, $8y5FN$react.useContext)($a8e1340dfdbe3a18$var$InternalDisclosureContext);
    let { isFocusVisible: isFocusVisibleWithin, focusProps: focusWithinProps } = (0, $8y5FN$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-DisclosurePanel',
        values: {
            isFocusVisibleWithin: isFocusVisibleWithin
        }
    });
    let DOMProps = (0, $8y5FN$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true,
        labelable: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8y5FN$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $8y5FN$reactariamergeProps.mergeProps)(DOMProps, renderProps, panelProps, focusWithinProps),
        ref: (0, $8y5FN$reactariamergeRefs.mergeRefs)(ref, panelRef),
        role: role,
        "data-focus-visible-within": isFocusVisibleWithin || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8y5FN$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                null
            ]
        ]
    }, props.children));
});


//# sourceMappingURL=Disclosure.cjs.map
