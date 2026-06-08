var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
require("../tooltip_vars.css");
var $d581447d0855914b$exports = require("../tooltip_vars_css.cjs");
var $523acaa4e80a06ae$exports = require("./context.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $96f7E$spectrumiconsuiAlertSmall = require("@spectrum-icons/ui/AlertSmall");
var $96f7E$reactariauseTooltipTrigger = require("react-aria/useTooltipTrigger");
var $96f7E$spectrumiconsuiInfoSmall = require("@spectrum-icons/ui/InfoSmall");
var $96f7E$reactariamergeProps = require("react-aria/mergeProps");
var $96f7E$react = require("react");
var $96f7E$spectrumiconsuiSuccessSmall = require("@spectrum-icons/ui/SuccessSmall");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Tooltip", function () { return $f1974881be69ddc4$export$28c660c63b792dea; });
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










let $f1974881be69ddc4$var$iconMap = {
    info: (0, ($parcel$interopDefault($96f7E$spectrumiconsuiInfoSmall))),
    positive: (0, ($parcel$interopDefault($96f7E$spectrumiconsuiSuccessSmall))),
    negative: (0, ($parcel$interopDefault($96f7E$spectrumiconsuiAlertSmall)))
};
const $f1974881be69ddc4$export$28c660c63b792dea = /*#__PURE__*/ (0, ($parcel$interopDefault($96f7E$react))).forwardRef(function Tooltip(props, ref) {
    let { ref: overlayRef, arrowProps: arrowProps, state: state, arrowRef: arrowRef, ...tooltipProviderProps } = (0, $96f7E$react.useContext)((0, $523acaa4e80a06ae$exports.TooltipContext));
    let defaultRef = (0, $96f7E$react.useRef)(null);
    overlayRef = overlayRef || defaultRef;
    let backupPlacement = props.placement;
    props = (0, $96f7E$reactariamergeProps.mergeProps)(props, tooltipProviderProps);
    let { variant: variant = 'neutral', placement: placement, isOpen: isOpen, showIcon: showIcon, ...otherProps } = props;
    if (placement == null) placement = backupPlacement ?? 'top';
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let { tooltipProps: tooltipProps } = (0, $96f7E$reactariauseTooltipTrigger.useTooltip)(props, state);
    // Sync ref with overlayRef from context.
    (0, $96f7E$react.useImperativeHandle)(ref, ()=>(0, $65aea7b37663976b$exports.createDOMRef)(overlayRef));
    let Icon = $f1974881be69ddc4$var$iconMap[variant];
    return /*#__PURE__*/ (0, ($parcel$interopDefault($96f7E$react))).createElement("div", {
        ...styleProps,
        ...tooltipProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d581447d0855914b$exports))), 'spectrum-Tooltip', `spectrum-Tooltip--${variant}`, `spectrum-Tooltip--${placement}`, {
            'is-open': isOpen,
            [`is-open--${placement}`]: isOpen
        }, styleProps.className),
        ref: overlayRef
    }, showIcon && variant !== 'neutral' && /*#__PURE__*/ (0, ($parcel$interopDefault($96f7E$react))).createElement(Icon, {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d581447d0855914b$exports))), 'spectrum-Tooltip-typeIcon'),
        "aria-hidden": true
    }), props.children && /*#__PURE__*/ (0, ($parcel$interopDefault($96f7E$react))).createElement("span", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d581447d0855914b$exports))), 'spectrum-Tooltip-label')
    }, props.children), /*#__PURE__*/ (0, ($parcel$interopDefault($96f7E$react))).createElement("span", {
        ...arrowProps,
        ref: arrowRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d581447d0855914b$exports))), 'spectrum-Tooltip-tip')
    }));
});


//# sourceMappingURL=Tooltip.cjs.map
