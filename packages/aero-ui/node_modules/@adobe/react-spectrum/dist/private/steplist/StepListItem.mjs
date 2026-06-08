import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import $8JI0T$intlStringsmjs from "./intlStrings.mjs";
import {StepListContext as $bfe1dd9c11a62094$export$66136572efa4af6e} from "./StepListContext.mjs";
import "../steplist_vars.css";
import $8JI0T$steplist_vars_cssmjs from "../steplist_vars_css.mjs";
import $8JI0T$spectrumiconsuiChevronRightMedium from "@spectrum-icons/ui/ChevronRightMedium";
import {FocusRing as $8JI0T$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $8JI0T$mergeProps} from "react-aria/mergeProps";
import $8JI0T$react, {useRef as $8JI0T$useRef, useContext as $8JI0T$useContext} from "react";
import {useHover as $8JI0T$useHover} from "react-aria/useHover";
import {useId as $8JI0T$useId} from "react-aria/useId";
import {useLocale as $8JI0T$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $8JI0T$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useNumberFormatter as $8JI0T$useNumberFormatter} from "react-aria/useNumberFormatter";
import {useStepListItem as $8JI0T$useStepListItem} from "react-aria/private/steplist/useStepListItem";
import {VisuallyHidden as $8JI0T$VisuallyHidden} from "react-aria/VisuallyHidden";


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














function $49159f98bfb552a9$export$87c2a8a94eda1754(props) {
    let { isDisabled: isDisabled, item: item } = props;
    let { key: key } = item;
    let ref = (0, $8JI0T$useRef)(null);
    let { direction: direction } = (0, $8JI0T$useLocale)();
    let state = (0, $8JI0T$useContext)((0, $bfe1dd9c11a62094$export$66136572efa4af6e));
    const isSelected = state.selectedKey === key;
    const isCompleted = state.isCompleted(key);
    const isItemDisabled = isDisabled || state.disabledKeys.has(key);
    let { stepProps: stepProps, stepStateProps: stepStateProps } = (0, $8JI0T$useStepListItem)({
        ...props,
        key: key
    }, state, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $8JI0T$useHover)({
        ...props,
        isDisabled: isItemDisabled || isSelected || props.isReadOnly
    });
    let stepStateText = '';
    const stringFormatter = (0, $8JI0T$useLocalizedStringFormatter)((0, ($parcel$interopDefault($8JI0T$intlStringsmjs))), '@react-spectrum/steplist');
    const numberFormatter = (0, $8JI0T$useNumberFormatter)();
    if (isSelected) stepStateText = stringFormatter.format('current');
    else if (isCompleted) stepStateText = stringFormatter.format('completed');
    else stepStateText = stringFormatter.format('notCompleted');
    let markerId = (0, $8JI0T$useId)();
    let stateId = (0, $8JI0T$useId)();
    let labelId = (0, $8JI0T$useId)();
    return /*#__PURE__*/ (0, $8JI0T$react).createElement("li", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-item')
    }, /*#__PURE__*/ (0, $8JI0T$react).createElement((0, $8JI0T$FocusRing), {
        within: true,
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $8JI0T$react).createElement("a", {
        ...(0, $8JI0T$mergeProps)(hoverProps, stepProps),
        "aria-labelledby": `${markerId} ${stateId} ${labelId}`,
        ref: ref,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-link', {
            'is-selected': isSelected && !isItemDisabled,
            'is-disabled': isItemDisabled,
            'is-hovered': isHovered,
            'is-completed': isCompleted,
            'is-selectable': state.isSelectable(key) && !isSelected
        })
    }, /*#__PURE__*/ (0, $8JI0T$react).createElement((0, $8JI0T$VisuallyHidden), {
        ...stepStateProps,
        id: stateId
    }, stepStateText), /*#__PURE__*/ (0, $8JI0T$react).createElement("div", {
        id: labelId,
        "aria-hidden": "true",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-label')
    }, item.rendered), /*#__PURE__*/ (0, $8JI0T$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-segment', {
            'is-completed': isCompleted
        })
    }, /*#__PURE__*/ (0, $8JI0T$react).createElement("svg", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-segmentLine'),
        xmlns: "http://www.w3.org/2000/svg",
        height: "100%",
        viewBox: "0 0 2 8",
        preserveAspectRatio: "none"
    }, /*#__PURE__*/ (0, $8JI0T$react).createElement("line", {
        x1: "1",
        y1: "0",
        x2: "1",
        y2: "8",
        vectorEffect: "non-scaling-stroke"
    })), /*#__PURE__*/ (0, $8JI0T$react).createElement((0, $8JI0T$spectrumiconsuiChevronRightMedium), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-chevron', {
            'is-reversed': direction === 'rtl'
        })
    })), /*#__PURE__*/ (0, $8JI0T$react).createElement("div", {
        "aria-hidden": "true",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-markerWrapper')
    }, /*#__PURE__*/ (0, $8JI0T$react).createElement("div", {
        id: markerId,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($8JI0T$steplist_vars_cssmjs))), 'spectrum-Steplist-marker')
    }, numberFormatter.format((item.index || 0) + 1))))));
}


export {$49159f98bfb552a9$export$87c2a8a94eda1754 as StepListItem};
//# sourceMappingURL=StepListItem.mjs.map
