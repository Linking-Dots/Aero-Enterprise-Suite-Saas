var $906ecc59dea2a2ae$exports = require("../overlays/Overlay.cjs");
var $523acaa4e80a06ae$exports = require("./context.cjs");
var $4YplM$reactariaprivateinteractionsuseFocusable = require("react-aria/private/interactions/useFocusable");
var $4YplM$reactariauseOverlayPosition = require("react-aria/useOverlayPosition");
var $4YplM$react = require("react");
var $4YplM$reactstatelyuseTooltipTriggerState = require("react-stately/useTooltipTriggerState");
var $4YplM$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $4YplM$reactariauseTooltipTrigger = require("react-aria/useTooltipTrigger");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TooltipTrigger", function () { return $ff31b6c981164b8a$export$8c610744efcf8a1d; });
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







const $ff31b6c981164b8a$var$DEFAULT_OFFSET = -1; // Offset needed to reach 4px/5px (med/large) distance between tooltip and trigger button
const $ff31b6c981164b8a$var$DEFAULT_CROSS_OFFSET = 0;
const $ff31b6c981164b8a$var$DEFAULT_SHOULD_CLOSE_ON_PRESS = true; // Whether the tooltip should close when the trigger is pressed
function $ff31b6c981164b8a$var$TooltipTrigger(props) {
    let { children: children, crossOffset: crossOffset = $ff31b6c981164b8a$var$DEFAULT_CROSS_OFFSET, isDisabled: isDisabled, offset: offset = $ff31b6c981164b8a$var$DEFAULT_OFFSET, trigger: triggerAction, shouldCloseOnPress: shouldCloseOnPress = $ff31b6c981164b8a$var$DEFAULT_SHOULD_CLOSE_ON_PRESS } = props;
    let [trigger, tooltip] = (0, ($parcel$interopDefault($4YplM$react))).Children.toArray(children);
    let state = (0, $4YplM$reactstatelyuseTooltipTriggerState.useTooltipTriggerState)(props);
    let tooltipTriggerRef = (0, $4YplM$react.useRef)(null);
    let overlayRef = (0, $4YplM$react.useRef)(null);
    let { triggerProps: triggerProps, tooltipProps: tooltipProps } = (0, $4YplM$reactariauseTooltipTrigger.useTooltipTrigger)({
        isDisabled: isDisabled,
        trigger: triggerAction,
        shouldCloseOnPress: shouldCloseOnPress
    }, state, tooltipTriggerRef);
    let [borderRadius, setBorderRadius] = (0, $4YplM$react.useState)(0);
    (0, $4YplM$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        if (overlayRef.current && state.isOpen) {
            let spectrumBorderRadius = window.getComputedStyle(overlayRef.current).borderRadius;
            if (spectrumBorderRadius !== '') setBorderRadius(parseInt(spectrumBorderRadius, 10));
        }
    }, [
        state.isOpen,
        overlayRef
    ]);
    let arrowRef = (0, $4YplM$react.useRef)(null);
    let [arrowWidth, setArrowWidth] = (0, $4YplM$react.useState)(0);
    (0, $4YplM$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        if (arrowRef.current && state.isOpen) setArrowWidth(arrowRef.current.getBoundingClientRect().width);
    }, [
        state.isOpen,
        arrowRef
    ]);
    let { overlayProps: overlayProps, arrowProps: arrowProps, placement: placement } = (0, $4YplM$reactariauseOverlayPosition.useOverlayPosition)({
        placement: props.placement || 'top',
        targetRef: tooltipTriggerRef,
        overlayRef: overlayRef,
        offset: offset,
        crossOffset: crossOffset,
        isOpen: state.isOpen,
        shouldFlip: props.shouldFlip,
        containerPadding: props.containerPadding,
        arrowSize: arrowWidth,
        arrowBoundaryOffset: borderRadius,
        onClose: ()=>state.close(true)
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($4YplM$react))).createElement((0, $4YplM$reactariaprivateinteractionsuseFocusable.FocusableProvider), {
        ...triggerProps,
        ref: tooltipTriggerRef
    }, trigger, /*#__PURE__*/ (0, ($parcel$interopDefault($4YplM$react))).createElement((0, $523acaa4e80a06ae$exports.TooltipContext).Provider, {
        value: {
            state: state,
            placement: placement,
            ref: overlayRef,
            UNSAFE_style: overlayProps.style,
            arrowProps: arrowProps,
            arrowRef: arrowRef,
            ...tooltipProps
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4YplM$react))).createElement((0, $906ecc59dea2a2ae$exports.Overlay), {
        isOpen: state.isOpen,
        nodeRef: overlayRef
    }, tooltip)));
}
// Support TooltipTrigger inside components using CollectionBuilder.
$ff31b6c981164b8a$var$TooltipTrigger.getCollectionNode = function*(props) {
    // Replaced the use of React.Children.toArray because it mutates the key prop.
    let childArray = [];
    (0, ($parcel$interopDefault($4YplM$react))).Children.forEach(props.children, (child)=>{
        if (/*#__PURE__*/ (0, ($parcel$interopDefault($4YplM$react))).isValidElement(child)) childArray.push(child);
    });
    let [trigger, tooltip] = childArray;
    yield {
        element: trigger,
        wrapper: (element)=>/*#__PURE__*/ (0, ($parcel$interopDefault($4YplM$react))).createElement($ff31b6c981164b8a$var$TooltipTrigger, {
                key: element.key,
                ...props
            }, element, tooltip)
    };
};
/**
 * TooltipTrigger wraps around a trigger element and a Tooltip. It handles opening and closing
 * the Tooltip when the user hovers over or focuses the trigger, and positioning the Tooltip
 * relative to the trigger.
 */ // We don't want getCollectionNode to show up in the type definition
let $ff31b6c981164b8a$export$8c610744efcf8a1d = $ff31b6c981164b8a$var$TooltipTrigger;


//# sourceMappingURL=TooltipTrigger.cjs.map
