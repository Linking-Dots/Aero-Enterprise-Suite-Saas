var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../button_vars.css");
var $869138cbe3b599dc$exports = require("../button_vars_css.cjs");
var $15e3b68ec42125a9$exports = require("../text/Text.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $jV5av$reactariauseButton = require("react-aria/useButton");
var $jV5av$spectrumiconsuiCornerTriangle = require("@spectrum-icons/ui/CornerTriangle");
var $jV5av$reactariaFocusRing = require("react-aria/FocusRing");
var $jV5av$reactariamergeProps = require("react-aria/mergeProps");
var $jV5av$react = require("react");
var $jV5av$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ActionButton", function () { return $183c5173677598aa$export$cfc7921d29ef7b80; });
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












const $183c5173677598aa$export$cfc7921d29ef7b80 = /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).forwardRef(function ActionButton(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'actionButton');
    let textProps = (0, $feede71cddc0c5f3$exports.useSlotProps)({
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-ActionButton-label')
    }, 'text');
    let { isQuiet: isQuiet, isDisabled: isDisabled, staticColor: staticColor, children: children, autoFocus: autoFocus, holdAffordance: // @ts-ignore (private)
    holdAffordance, hideButtonText: // @ts-ignore (private)
    hideButtonText, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref);
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $jV5av$reactariauseButton.useButton)(props, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $jV5av$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let isTextOnly = (0, ($parcel$interopDefault($jV5av$react))).Children.toArray(props.children).every((c)=>!/*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).isValidElement(c));
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).createElement((0, $jV5av$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'focus-ring'),
        autoFocus: autoFocus
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).createElement("button", {
        ...styleProps,
        ...(0, $jV5av$reactariamergeProps.mergeProps)(buttonProps, hoverProps),
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-ActionButton', {
            'spectrum-ActionButton--quiet': isQuiet,
            'spectrum-ActionButton--staticColor': !!staticColor,
            'spectrum-ActionButton--staticWhite': staticColor === 'white',
            'spectrum-ActionButton--staticBlack': staticColor === 'black',
            'is-active': isPressed,
            'is-disabled': isDisabled,
            'is-hovered': isHovered
        }, styleProps.className)
    }, holdAffordance && /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).createElement((0, ($parcel$interopDefault($jV5av$spectrumiconsuiCornerTriangle))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-ActionButton-hold')
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).createElement((0, $feede71cddc0c5f3$exports.ClearSlots), null, /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            icon: {
                size: 'S',
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($869138cbe3b599dc$exports))), 'spectrum-Icon', {
                    'spectrum-ActionGroup-itemIcon': hideButtonText
                })
            },
            text: {
                ...textProps
            }
        }
    }, typeof children === 'string' || isTextOnly ? /*#__PURE__*/ (0, ($parcel$interopDefault($jV5av$react))).createElement((0, $15e3b68ec42125a9$exports.Text), null, children) : children))));
});


//# sourceMappingURL=ActionButton.cjs.map
