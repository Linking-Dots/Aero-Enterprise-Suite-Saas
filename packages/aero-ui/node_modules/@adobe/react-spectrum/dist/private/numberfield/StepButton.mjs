import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../stepper_vars.css";
import $6kEdg$stepper_vars_cssmjs from "../stepper_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProvider as $71dfb0e0358a12de$export$693cdb10cec23617, useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import $6kEdg$spectrumiconsworkflowAdd from "@spectrum-icons/workflow/Add";
import {useButton as $6kEdg$useButton} from "react-aria/useButton";
import $6kEdg$spectrumiconsuiChevronDownSmall from "@spectrum-icons/ui/ChevronDownSmall";
import $6kEdg$spectrumiconsuiChevronUpSmall from "@spectrum-icons/ui/ChevronUpSmall";
import {FocusRing as $6kEdg$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $6kEdg$mergeProps} from "react-aria/mergeProps";
import $6kEdg$react from "react";
import $6kEdg$spectrumiconsworkflowRemove from "@spectrum-icons/workflow/Remove";
import {useHover as $6kEdg$useHover} from "react-aria/useHover";


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












const $9f85b9d1ee72dcc2$export$b2f6b60c1d32d6aa = /*#__PURE__*/ (0, $6kEdg$react).forwardRef(function StepButton(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { scale: scale } = (0, $71dfb0e0358a12de$export$693cdb10cec23617)();
    let { direction: direction, isDisabled: isDisabled, isQuiet: isQuiet } = props;
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref);
    /**
   * Must use div for now because Safari pointer event bugs on disabled form elements.
   * Link https://bugs.webkit.org/show_bug.cgi?id=219188.
   */ let { buttonProps: buttonProps, isPressed: isPressed } = (0, $6kEdg$useButton)({
        ...props,
        elementType: 'div'
    }, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $6kEdg$useHover)(props);
    return /*#__PURE__*/ (0, $6kEdg$react).createElement((0, $6kEdg$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6kEdg$stepper_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $6kEdg$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6kEdg$stepper_vars_cssmjs))), 'spectrum-Stepper-button', {
            'spectrum-Stepper-button--stepUp': direction === 'up',
            'spectrum-Stepper-button--stepDown': direction === 'down',
            'spectrum-Stepper-button--isQuiet': isQuiet,
            'is-hovered': isHovered,
            'is-active': isPressed,
            'is-disabled': isDisabled
        }),
        ...(0, $6kEdg$mergeProps)(hoverProps, buttonProps),
        ref: domRef
    }, direction === 'up' && scale === 'large' && /*#__PURE__*/ (0, $6kEdg$react).createElement((0, $6kEdg$spectrumiconsworkflowAdd), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6kEdg$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepUpIcon'),
        size: "S"
    }), direction === 'up' && scale === 'medium' && /*#__PURE__*/ (0, $6kEdg$react).createElement((0, $6kEdg$spectrumiconsuiChevronUpSmall), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6kEdg$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepUpIcon')
    }), direction === 'down' && scale === 'large' && /*#__PURE__*/ (0, $6kEdg$react).createElement((0, $6kEdg$spectrumiconsworkflowRemove), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6kEdg$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepDownIcon'),
        size: "S"
    }), direction === 'down' && scale === 'medium' && /*#__PURE__*/ (0, $6kEdg$react).createElement((0, $6kEdg$spectrumiconsuiChevronDownSmall), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6kEdg$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepDownIcon')
    })));
});


export {$9f85b9d1ee72dcc2$export$b2f6b60c1d32d6aa as StepButton};
//# sourceMappingURL=StepButton.mjs.map
