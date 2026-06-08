import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../stepper_vars.css";
import $bHwJE$stepper_vars_cssmjs from "../stepper_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProvider as $089943c7a219141c$export$693cdb10cec23617, useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import $bHwJE$spectrumiconsworkflowAdd from "@spectrum-icons/workflow/Add";
import {useButton as $bHwJE$useButton} from "react-aria/useButton";
import $bHwJE$spectrumiconsuiChevronDownSmall from "@spectrum-icons/ui/ChevronDownSmall";
import $bHwJE$spectrumiconsuiChevronUpSmall from "@spectrum-icons/ui/ChevronUpSmall";
import {FocusRing as $bHwJE$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $bHwJE$mergeProps} from "react-aria/mergeProps";
import $bHwJE$react from "react";
import $bHwJE$spectrumiconsworkflowRemove from "@spectrum-icons/workflow/Remove";
import {useHover as $bHwJE$useHover} from "react-aria/useHover";


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












const $f06f822f6b0bbb02$export$b2f6b60c1d32d6aa = /*#__PURE__*/ (0, $bHwJE$react).forwardRef(function StepButton(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { scale: scale } = (0, $089943c7a219141c$export$693cdb10cec23617)();
    let { direction: direction, isDisabled: isDisabled, isQuiet: isQuiet } = props;
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref);
    /**
   * Must use div for now because Safari pointer event bugs on disabled form elements.
   * Link https://bugs.webkit.org/show_bug.cgi?id=219188.
   */ let { buttonProps: buttonProps, isPressed: isPressed } = (0, $bHwJE$useButton)({
        ...props,
        elementType: 'div'
    }, domRef);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $bHwJE$useHover)(props);
    return /*#__PURE__*/ (0, $bHwJE$react).createElement((0, $bHwJE$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bHwJE$stepper_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $bHwJE$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bHwJE$stepper_vars_cssmjs))), 'spectrum-Stepper-button', {
            'spectrum-Stepper-button--stepUp': direction === 'up',
            'spectrum-Stepper-button--stepDown': direction === 'down',
            'spectrum-Stepper-button--isQuiet': isQuiet,
            'is-hovered': isHovered,
            'is-active': isPressed,
            'is-disabled': isDisabled
        }),
        ...(0, $bHwJE$mergeProps)(hoverProps, buttonProps),
        ref: domRef
    }, direction === 'up' && scale === 'large' && /*#__PURE__*/ (0, $bHwJE$react).createElement((0, $bHwJE$spectrumiconsworkflowAdd), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bHwJE$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepUpIcon'),
        size: "S"
    }), direction === 'up' && scale === 'medium' && /*#__PURE__*/ (0, $bHwJE$react).createElement((0, $bHwJE$spectrumiconsuiChevronUpSmall), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bHwJE$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepUpIcon')
    }), direction === 'down' && scale === 'large' && /*#__PURE__*/ (0, $bHwJE$react).createElement((0, $bHwJE$spectrumiconsworkflowRemove), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bHwJE$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepDownIcon'),
        size: "S"
    }), direction === 'down' && scale === 'medium' && /*#__PURE__*/ (0, $bHwJE$react).createElement((0, $bHwJE$spectrumiconsuiChevronDownSmall), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bHwJE$stepper_vars_cssmjs))), 'spectrum-Stepper-button-icon', 'spectrum-Stepper-stepDownIcon')
    })));
});


export {$f06f822f6b0bbb02$export$b2f6b60c1d32d6aa as StepButton};
//# sourceMappingURL=StepButton.js.map
