import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {createDOMRef as $c234463e9ef56637$export$a5795cc979dfae80} from "../utils/useDOMRef.js";
import "../tooltip_vars.css";
import $hpkqI$tooltip_vars_cssmjs from "../tooltip_vars_css.mjs";
import {TooltipContext as $b4d5dc28ba54bd9f$export$39ae08fa83328b12} from "./context.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $hpkqI$spectrumiconsuiAlertSmall from "@spectrum-icons/ui/AlertSmall";
import {useTooltip as $hpkqI$useTooltip} from "react-aria/useTooltipTrigger";
import $hpkqI$spectrumiconsuiInfoSmall from "@spectrum-icons/ui/InfoSmall";
import {mergeProps as $hpkqI$mergeProps} from "react-aria/mergeProps";
import $hpkqI$react, {useContext as $hpkqI$useContext, useRef as $hpkqI$useRef, useImperativeHandle as $hpkqI$useImperativeHandle} from "react";
import $hpkqI$spectrumiconsuiSuccessSmall from "@spectrum-icons/ui/SuccessSmall";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
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










let $3f07dad5c19b53b6$var$iconMap = {
    info: (0, $hpkqI$spectrumiconsuiInfoSmall),
    positive: (0, $hpkqI$spectrumiconsuiSuccessSmall),
    negative: (0, $hpkqI$spectrumiconsuiAlertSmall)
};
const $3f07dad5c19b53b6$export$28c660c63b792dea = /*#__PURE__*/ (0, $hpkqI$react).forwardRef(function Tooltip(props, ref) {
    let { ref: overlayRef, arrowProps: arrowProps, state: state, arrowRef: arrowRef, ...tooltipProviderProps } = (0, $hpkqI$useContext)((0, $b4d5dc28ba54bd9f$export$39ae08fa83328b12));
    let defaultRef = (0, $hpkqI$useRef)(null);
    overlayRef = overlayRef || defaultRef;
    let backupPlacement = props.placement;
    props = (0, $hpkqI$mergeProps)(props, tooltipProviderProps);
    let { variant: variant = 'neutral', placement: placement, isOpen: isOpen, showIcon: showIcon, ...otherProps } = props;
    if (placement == null) placement = backupPlacement !== null && backupPlacement !== void 0 ? backupPlacement : 'top';
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let { tooltipProps: tooltipProps } = (0, $hpkqI$useTooltip)(props, state);
    // Sync ref with overlayRef from context.
    (0, $hpkqI$useImperativeHandle)(ref, ()=>(0, $c234463e9ef56637$export$a5795cc979dfae80)(overlayRef));
    let Icon = $3f07dad5c19b53b6$var$iconMap[variant];
    return /*#__PURE__*/ (0, $hpkqI$react).createElement("div", {
        ...styleProps,
        ...tooltipProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hpkqI$tooltip_vars_cssmjs))), 'spectrum-Tooltip', `spectrum-Tooltip--${variant}`, `spectrum-Tooltip--${placement}`, {
            'is-open': isOpen,
            [`is-open--${placement}`]: isOpen
        }, styleProps.className),
        ref: overlayRef
    }, showIcon && variant !== 'neutral' && /*#__PURE__*/ (0, $hpkqI$react).createElement(Icon, {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hpkqI$tooltip_vars_cssmjs))), 'spectrum-Tooltip-typeIcon'),
        "aria-hidden": true
    }), props.children && /*#__PURE__*/ (0, $hpkqI$react).createElement("span", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hpkqI$tooltip_vars_cssmjs))), 'spectrum-Tooltip-label')
    }, props.children), /*#__PURE__*/ (0, $hpkqI$react).createElement("span", {
        ...arrowProps,
        ref: arrowRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($hpkqI$tooltip_vars_cssmjs))), 'spectrum-Tooltip-tip')
    }));
});


export {$3f07dad5c19b53b6$export$28c660c63b792dea as Tooltip};
//# sourceMappingURL=Tooltip.js.map
