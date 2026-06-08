var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../stepper_vars.css");
var $15de4d4dab96ad82$exports = require("../stepper_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $cCEYY$spectrumiconsworkflowAdd = require("@spectrum-icons/workflow/Add");
var $cCEYY$reactariauseButton = require("react-aria/useButton");
var $cCEYY$spectrumiconsuiChevronDownSmall = require("@spectrum-icons/ui/ChevronDownSmall");
var $cCEYY$spectrumiconsuiChevronUpSmall = require("@spectrum-icons/ui/ChevronUpSmall");
var $cCEYY$reactariaFocusRing = require("react-aria/FocusRing");
var $cCEYY$reactariamergeProps = require("react-aria/mergeProps");
var $cCEYY$react = require("react");
var $cCEYY$spectrumiconsworkflowRemove = require("@spectrum-icons/workflow/Remove");
var $cCEYY$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "StepButton", function () { return $b7aad1ed7fcdf769$export$b2f6b60c1d32d6aa; });
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












const $b7aad1ed7fcdf769$export$b2f6b60c1d32d6aa = /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).forwardRef(function StepButton(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { scale: scale } = (0, $544fc82701fc93e9$exports.useProvider)();
    let { direction: direction, isDisabled: isDisabled, isQuiet: isQuiet } = props;
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref);
    /**
   * Must use div for now because Safari pointer event bugs on disabled form elements.
   * Link https://bugs.webkit.org/show_bug.cgi?id=219188.
   */ let { buttonProps: buttonProps, isPressed: isPressed } = (0, $cCEYY$reactariauseButton.useButton)({
        ...props,
        elementType: 'div'
    }, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $cCEYY$reactariauseHover.useHover)(props);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).createElement((0, $cCEYY$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'focus-ring')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-button', {
            'spectrum-Stepper-button--stepUp': direction === 'up',
            'spectrum-Stepper-button--stepDown': direction === 'down',
            'spectrum-Stepper-button--isQuiet': isQuiet,
            'is-hovered': isHovered,
            'is-active': isPressed,
            'is-disabled': isDisabled
        }),
        ...(0, $cCEYY$reactariamergeProps.mergeProps)(hoverProps, buttonProps),
        ref: domRef
    }, direction === 'up' && scale === 'large' && /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).createElement((0, ($parcel$interopDefault($cCEYY$spectrumiconsworkflowAdd))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepUpIcon'),
        size: "S"
    }), direction === 'up' && scale === 'medium' && /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).createElement((0, ($parcel$interopDefault($cCEYY$spectrumiconsuiChevronUpSmall))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepUpIcon')
    }), direction === 'down' && scale === 'large' && /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).createElement((0, ($parcel$interopDefault($cCEYY$spectrumiconsworkflowRemove))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepDownIcon'),
        size: "S"
    }), direction === 'down' && scale === 'medium' && /*#__PURE__*/ (0, ($parcel$interopDefault($cCEYY$react))).createElement((0, ($parcel$interopDefault($cCEYY$spectrumiconsuiChevronDownSmall))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($15de4d4dab96ad82$exports))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepDownIcon')
    })));
});


//# sourceMappingURL=StepButton.cjs.map
