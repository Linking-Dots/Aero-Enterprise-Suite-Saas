import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import $BIvsM$intlStringsjs from "./intlStrings.js";
import {StepListContext as $9831a32a4a052188$export$66136572efa4af6e} from "./StepListContext.js";
import "../steplist_vars.css";
import $BIvsM$steplist_vars_cssmjs from "../steplist_vars_css.mjs";
import $BIvsM$spectrumiconsuiChevronRightMedium from "@spectrum-icons/ui/ChevronRightMedium";
import {FocusRing as $BIvsM$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $BIvsM$mergeProps} from "react-aria/mergeProps";
import $BIvsM$react, {useRef as $BIvsM$useRef, useContext as $BIvsM$useContext} from "react";
import {useHover as $BIvsM$useHover} from "react-aria/useHover";
import {useId as $BIvsM$useId} from "react-aria/useId";
import {useLocale as $BIvsM$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $BIvsM$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useNumberFormatter as $BIvsM$useNumberFormatter} from "react-aria/useNumberFormatter";
import {useStepListItem as $BIvsM$useStepListItem} from "react-aria/private/steplist/useStepListItem";
import {VisuallyHidden as $BIvsM$VisuallyHidden} from "react-aria/VisuallyHidden";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 














function $84f7e0d248669720$export$87c2a8a94eda1754(props) {
    let { isDisabled: isDisabled, item: item } = props;
    let { key: key } = item;
    let ref = (0, $BIvsM$useRef)(null);
    let { direction: direction } = (0, $BIvsM$useLocale)();
    let state = (0, $BIvsM$useContext)((0, $9831a32a4a052188$export$66136572efa4af6e));
    const isSelected = state.selectedKey === key;
    const isCompleted = state.isCompleted(key);
    const isItemDisabled = isDisabled || state.disabledKeys.has(key);
    let { stepProps: stepProps, stepStateProps: stepStateProps } = (0, $BIvsM$useStepListItem)({
        ...props,
        key: key
    }, state, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $BIvsM$useHover)({
        ...props,
        isDisabled: isItemDisabled || isSelected || props.isReadOnly
    });
    let stepStateText = '';
    const stringFormatter = (0, $BIvsM$useLocalizedStringFormatter)((0, ($parcel$interopDefault($BIvsM$intlStringsjs))), '@react-spectrum/steplist');
    const numberFormatter = (0, $BIvsM$useNumberFormatter)();
    if (isSelected) stepStateText = stringFormatter.format('current');
    else if (isCompleted) stepStateText = stringFormatter.format('completed');
    else stepStateText = stringFormatter.format('notCompleted');
    let markerId = (0, $BIvsM$useId)();
    let stateId = (0, $BIvsM$useId)();
    let labelId = (0, $BIvsM$useId)();
    return /*#__PURE__*/ (0, $BIvsM$react).createElement("li", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-item')
    }, /*#__PURE__*/ (0, $BIvsM$react).createElement((0, $BIvsM$FocusRing), {
        within: true,
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $BIvsM$react).createElement("a", {
        ...(0, $BIvsM$mergeProps)(hoverProps, stepProps),
        "aria-labelledby": `${markerId} ${stateId} ${labelId}`,
        ref: ref,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-link', {
            'is-selected': isSelected && !isItemDisabled,
            'is-disabled': isItemDisabled,
            'is-hovered': isHovered,
            'is-completed': isCompleted,
            'is-selectable': state.isSelectable(key) && !isSelected
        })
    }, /*#__PURE__*/ (0, $BIvsM$react).createElement((0, $BIvsM$VisuallyHidden), {
        ...stepStateProps,
        id: stateId
    }, stepStateText), /*#__PURE__*/ (0, $BIvsM$react).createElement("div", {
        id: labelId,
        "aria-hidden": "true",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-label')
    }, item.rendered), /*#__PURE__*/ (0, $BIvsM$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-segment', {
            'is-completed': isCompleted
        })
    }, /*#__PURE__*/ (0, $BIvsM$react).createElement("svg", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-segmentLine'),
        xmlns: "http://www.w3.org/2000/svg",
        height: "100%",
        viewBox: "0 0 2 8",
        preserveAspectRatio: "none"
    }, /*#__PURE__*/ (0, $BIvsM$react).createElement("line", {
        x1: "1",
        y1: "0",
        x2: "1",
        y2: "8",
        vectorEffect: "non-scaling-stroke"
    })), /*#__PURE__*/ (0, $BIvsM$react).createElement((0, $BIvsM$spectrumiconsuiChevronRightMedium), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-chevron', {
            'is-reversed': direction === 'rtl'
        })
    })), /*#__PURE__*/ (0, $BIvsM$react).createElement("div", {
        "aria-hidden": "true",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-markerWrapper')
    }, /*#__PURE__*/ (0, $BIvsM$react).createElement("div", {
        id: markerId,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($BIvsM$steplist_vars_cssmjs))), 'spectrum-Steplist-marker')
    }, numberFormatter.format((item.index || 0) + 1))))));
}


export {$84f7e0d248669720$export$87c2a8a94eda1754 as StepListItem};
//# sourceMappingURL=StepListItem.js.map
