import {Overlay as $90fcff6c53c7cf60$export$c6fdb837b070b4ff} from "../overlays/Overlay.mjs";
import {TooltipContext as $9981aa7c34a62ebd$export$39ae08fa83328b12} from "./context.mjs";
import {FocusableProvider as $584dC$FocusableProvider} from "react-aria/private/interactions/useFocusable";
import {useOverlayPosition as $584dC$useOverlayPosition} from "react-aria/useOverlayPosition";
import $584dC$react, {useRef as $584dC$useRef, useState as $584dC$useState} from "react";
import {useTooltipTriggerState as $584dC$useTooltipTriggerState} from "react-stately/useTooltipTriggerState";
import {useLayoutEffect as $584dC$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useTooltipTrigger as $584dC$useTooltipTrigger} from "react-aria/useTooltipTrigger";

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







const $1db3f28f9989b2cd$var$DEFAULT_OFFSET = -1; // Offset needed to reach 4px/5px (med/large) distance between tooltip and trigger button
const $1db3f28f9989b2cd$var$DEFAULT_CROSS_OFFSET = 0;
const $1db3f28f9989b2cd$var$DEFAULT_SHOULD_CLOSE_ON_PRESS = true; // Whether the tooltip should close when the trigger is pressed
function $1db3f28f9989b2cd$var$TooltipTrigger(props) {
    let { children: children, crossOffset: crossOffset = $1db3f28f9989b2cd$var$DEFAULT_CROSS_OFFSET, isDisabled: isDisabled, offset: offset = $1db3f28f9989b2cd$var$DEFAULT_OFFSET, trigger: triggerAction, shouldCloseOnPress: shouldCloseOnPress = $1db3f28f9989b2cd$var$DEFAULT_SHOULD_CLOSE_ON_PRESS } = props;
    let [trigger, tooltip] = (0, $584dC$react).Children.toArray(children);
    let state = (0, $584dC$useTooltipTriggerState)(props);
    let tooltipTriggerRef = (0, $584dC$useRef)(null);
    let overlayRef = (0, $584dC$useRef)(null);
    let { triggerProps: triggerProps, tooltipProps: tooltipProps } = (0, $584dC$useTooltipTrigger)({
        isDisabled: isDisabled,
        trigger: triggerAction,
        shouldCloseOnPress: shouldCloseOnPress
    }, state, tooltipTriggerRef);
    let [borderRadius, setBorderRadius] = (0, $584dC$useState)(0);
    (0, $584dC$useLayoutEffect)(()=>{
        if (overlayRef.current && state.isOpen) {
            let spectrumBorderRadius = window.getComputedStyle(overlayRef.current).borderRadius;
            if (spectrumBorderRadius !== '') setBorderRadius(parseInt(spectrumBorderRadius, 10));
        }
    }, [
        state.isOpen,
        overlayRef
    ]);
    let arrowRef = (0, $584dC$useRef)(null);
    let [arrowWidth, setArrowWidth] = (0, $584dC$useState)(0);
    (0, $584dC$useLayoutEffect)(()=>{
        if (arrowRef.current && state.isOpen) setArrowWidth(arrowRef.current.getBoundingClientRect().width);
    }, [
        state.isOpen,
        arrowRef
    ]);
    let { overlayProps: overlayProps, arrowProps: arrowProps, placement: placement } = (0, $584dC$useOverlayPosition)({
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
    return /*#__PURE__*/ (0, $584dC$react).createElement((0, $584dC$FocusableProvider), {
        ...triggerProps,
        ref: tooltipTriggerRef
    }, trigger, /*#__PURE__*/ (0, $584dC$react).createElement((0, $9981aa7c34a62ebd$export$39ae08fa83328b12).Provider, {
        value: {
            state: state,
            placement: placement,
            ref: overlayRef,
            UNSAFE_style: overlayProps.style,
            arrowProps: arrowProps,
            arrowRef: arrowRef,
            ...tooltipProps
        }
    }, /*#__PURE__*/ (0, $584dC$react).createElement((0, $90fcff6c53c7cf60$export$c6fdb837b070b4ff), {
        isOpen: state.isOpen,
        nodeRef: overlayRef
    }, tooltip)));
}
// Support TooltipTrigger inside components using CollectionBuilder.
$1db3f28f9989b2cd$var$TooltipTrigger.getCollectionNode = function*(props) {
    // Replaced the use of React.Children.toArray because it mutates the key prop.
    let childArray = [];
    (0, $584dC$react).Children.forEach(props.children, (child)=>{
        if (/*#__PURE__*/ (0, $584dC$react).isValidElement(child)) childArray.push(child);
    });
    let [trigger, tooltip] = childArray;
    yield {
        element: trigger,
        wrapper: (element)=>/*#__PURE__*/ (0, $584dC$react).createElement($1db3f28f9989b2cd$var$TooltipTrigger, {
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
let $1db3f28f9989b2cd$export$8c610744efcf8a1d = $1db3f28f9989b2cd$var$TooltipTrigger;


export {$1db3f28f9989b2cd$export$8c610744efcf8a1d as TooltipTrigger};
//# sourceMappingURL=TooltipTrigger.mjs.map
