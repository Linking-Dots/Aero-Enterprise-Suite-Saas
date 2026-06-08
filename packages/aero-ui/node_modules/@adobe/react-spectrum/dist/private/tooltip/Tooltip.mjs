import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {createDOMRef as $3c2c983d5210446c$export$a5795cc979dfae80} from "../utils/useDOMRef.mjs";
import "../tooltip_vars.css";
import $941gK$tooltip_vars_cssmjs from "../tooltip_vars_css.mjs";
import {TooltipContext as $9981aa7c34a62ebd$export$39ae08fa83328b12} from "./context.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $941gK$spectrumiconsuiAlertSmall from "@spectrum-icons/ui/AlertSmall";
import {useTooltip as $941gK$useTooltip} from "react-aria/useTooltipTrigger";
import $941gK$spectrumiconsuiInfoSmall from "@spectrum-icons/ui/InfoSmall";
import {mergeProps as $941gK$mergeProps} from "react-aria/mergeProps";
import $941gK$react, {useContext as $941gK$useContext, useRef as $941gK$useRef, useImperativeHandle as $941gK$useImperativeHandle} from "react";
import $941gK$spectrumiconsuiSuccessSmall from "@spectrum-icons/ui/SuccessSmall";


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










let $91ff1bc0856186a6$var$iconMap = {
    info: (0, $941gK$spectrumiconsuiInfoSmall),
    positive: (0, $941gK$spectrumiconsuiSuccessSmall),
    negative: (0, $941gK$spectrumiconsuiAlertSmall)
};
const $91ff1bc0856186a6$export$28c660c63b792dea = /*#__PURE__*/ (0, $941gK$react).forwardRef(function Tooltip(props, ref) {
    let { ref: overlayRef, arrowProps: arrowProps, state: state, arrowRef: arrowRef, ...tooltipProviderProps } = (0, $941gK$useContext)((0, $9981aa7c34a62ebd$export$39ae08fa83328b12));
    let defaultRef = (0, $941gK$useRef)(null);
    overlayRef = overlayRef || defaultRef;
    let backupPlacement = props.placement;
    props = (0, $941gK$mergeProps)(props, tooltipProviderProps);
    let { variant: variant = 'neutral', placement: placement, isOpen: isOpen, showIcon: showIcon, ...otherProps } = props;
    if (placement == null) placement = backupPlacement ?? 'top';
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let { tooltipProps: tooltipProps } = (0, $941gK$useTooltip)(props, state);
    // Sync ref with overlayRef from context.
    (0, $941gK$useImperativeHandle)(ref, ()=>(0, $3c2c983d5210446c$export$a5795cc979dfae80)(overlayRef));
    let Icon = $91ff1bc0856186a6$var$iconMap[variant];
    return /*#__PURE__*/ (0, $941gK$react).createElement("div", {
        ...styleProps,
        ...tooltipProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($941gK$tooltip_vars_cssmjs))), 'spectrum-Tooltip', `spectrum-Tooltip--${variant}`, `spectrum-Tooltip--${placement}`, {
            'is-open': isOpen,
            [`is-open--${placement}`]: isOpen
        }, styleProps.className),
        ref: overlayRef
    }, showIcon && variant !== 'neutral' && /*#__PURE__*/ (0, $941gK$react).createElement(Icon, {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($941gK$tooltip_vars_cssmjs))), 'spectrum-Tooltip-typeIcon'),
        "aria-hidden": true
    }), props.children && /*#__PURE__*/ (0, $941gK$react).createElement("span", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($941gK$tooltip_vars_cssmjs))), 'spectrum-Tooltip-label')
    }, props.children), /*#__PURE__*/ (0, $941gK$react).createElement("span", {
        ...arrowProps,
        ref: arrowRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($941gK$tooltip_vars_cssmjs))), 'spectrum-Tooltip-tip')
    }));
});


export {$91ff1bc0856186a6$export$28c660c63b792dea as Tooltip};
//# sourceMappingURL=Tooltip.mjs.map
