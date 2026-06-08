import {Overlay as $d73ca11fb7e7e69a$export$c6fdb837b070b4ff} from "../overlays/Overlay.js";
import {TooltipContext as $b4d5dc28ba54bd9f$export$39ae08fa83328b12} from "./context.js";
import {FocusableProvider as $940sd$FocusableProvider} from "react-aria/private/interactions/useFocusable";
import {useOverlayPosition as $940sd$useOverlayPosition} from "react-aria/useOverlayPosition";
import $940sd$react, {useRef as $940sd$useRef, useState as $940sd$useState} from "react";
import {useTooltipTriggerState as $940sd$useTooltipTriggerState} from "react-stately/useTooltipTriggerState";
import {useLayoutEffect as $940sd$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useTooltipTrigger as $940sd$useTooltipTrigger} from "react-aria/useTooltipTrigger";

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







const $3e76a1633f5aa2b7$var$DEFAULT_OFFSET = -1; // Offset needed to reach 4px/5px (med/large) distance between tooltip and trigger button
const $3e76a1633f5aa2b7$var$DEFAULT_CROSS_OFFSET = 0;
const $3e76a1633f5aa2b7$var$DEFAULT_SHOULD_CLOSE_ON_PRESS = true; // Whether the tooltip should close when the trigger is pressed
function $3e76a1633f5aa2b7$var$TooltipTrigger(props) {
    let { children: children, crossOffset: crossOffset = $3e76a1633f5aa2b7$var$DEFAULT_CROSS_OFFSET, isDisabled: isDisabled, offset: offset = $3e76a1633f5aa2b7$var$DEFAULT_OFFSET, trigger: triggerAction, shouldCloseOnPress: shouldCloseOnPress = $3e76a1633f5aa2b7$var$DEFAULT_SHOULD_CLOSE_ON_PRESS } = props;
    let [trigger, tooltip] = (0, $940sd$react).Children.toArray(children);
    let state = (0, $940sd$useTooltipTriggerState)(props);
    let tooltipTriggerRef = (0, $940sd$useRef)(null);
    let overlayRef = (0, $940sd$useRef)(null);
    let { triggerProps: triggerProps, tooltipProps: tooltipProps } = (0, $940sd$useTooltipTrigger)({
        isDisabled: isDisabled,
        trigger: triggerAction,
        shouldCloseOnPress: shouldCloseOnPress
    }, state, tooltipTriggerRef);
    let [borderRadius, setBorderRadius] = (0, $940sd$useState)(0);
    (0, $940sd$useLayoutEffect)(()=>{
        if (overlayRef.current && state.isOpen) {
            let spectrumBorderRadius = window.getComputedStyle(overlayRef.current).borderRadius;
            if (spectrumBorderRadius !== '') setBorderRadius(parseInt(spectrumBorderRadius, 10));
        }
    }, [
        state.isOpen,
        overlayRef
    ]);
    let arrowRef = (0, $940sd$useRef)(null);
    let [arrowWidth, setArrowWidth] = (0, $940sd$useState)(0);
    (0, $940sd$useLayoutEffect)(()=>{
        if (arrowRef.current && state.isOpen) setArrowWidth(arrowRef.current.getBoundingClientRect().width);
    }, [
        state.isOpen,
        arrowRef
    ]);
    let { overlayProps: overlayProps, arrowProps: arrowProps, placement: placement } = (0, $940sd$useOverlayPosition)({
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
    return /*#__PURE__*/ (0, $940sd$react).createElement((0, $940sd$FocusableProvider), {
        ...triggerProps,
        ref: tooltipTriggerRef
    }, trigger, /*#__PURE__*/ (0, $940sd$react).createElement((0, $b4d5dc28ba54bd9f$export$39ae08fa83328b12).Provider, {
        value: {
            state: state,
            placement: placement,
            ref: overlayRef,
            UNSAFE_style: overlayProps.style,
            arrowProps: arrowProps,
            arrowRef: arrowRef,
            ...tooltipProps
        }
    }, /*#__PURE__*/ (0, $940sd$react).createElement((0, $d73ca11fb7e7e69a$export$c6fdb837b070b4ff), {
        isOpen: state.isOpen,
        nodeRef: overlayRef
    }, tooltip)));
}
// Support TooltipTrigger inside components using CollectionBuilder.
$3e76a1633f5aa2b7$var$TooltipTrigger.getCollectionNode = function*(props) {
    // Replaced the use of React.Children.toArray because it mutates the key prop.
    let childArray = [];
    (0, $940sd$react).Children.forEach(props.children, (child)=>{
        if (/*#__PURE__*/ (0, $940sd$react).isValidElement(child)) childArray.push(child);
    });
    let [trigger, tooltip] = childArray;
    yield {
        element: trigger,
        wrapper: (element)=>/*#__PURE__*/ (0, $940sd$react).createElement($3e76a1633f5aa2b7$var$TooltipTrigger, {
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
let $3e76a1633f5aa2b7$export$8c610744efcf8a1d = $3e76a1633f5aa2b7$var$TooltipTrigger;


export {$3e76a1633f5aa2b7$export$8c610744efcf8a1d as TooltipTrigger};
//# sourceMappingURL=TooltipTrigger.js.map
