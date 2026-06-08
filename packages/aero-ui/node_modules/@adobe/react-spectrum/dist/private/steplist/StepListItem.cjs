var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $4e841e3114791c56$exports = require("./intlStrings.cjs");
var $6f2b983372959298$exports = require("./StepListContext.cjs");
require("../steplist_vars.css");
var $d97c8cb44f9e179c$exports = require("../steplist_vars_css.cjs");
var $9ihVH$spectrumiconsuiChevronRightMedium = require("@spectrum-icons/ui/ChevronRightMedium");
var $9ihVH$reactariaFocusRing = require("react-aria/FocusRing");
var $9ihVH$reactariamergeProps = require("react-aria/mergeProps");
var $9ihVH$react = require("react");
var $9ihVH$reactariauseHover = require("react-aria/useHover");
var $9ihVH$reactariauseId = require("react-aria/useId");
var $9ihVH$reactariaI18nProvider = require("react-aria/I18nProvider");
var $9ihVH$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");
var $9ihVH$reactariauseNumberFormatter = require("react-aria/useNumberFormatter");
var $9ihVH$reactariaprivatesteplistuseStepListItem = require("react-aria/private/steplist/useStepListItem");
var $9ihVH$reactariaVisuallyHidden = require("react-aria/VisuallyHidden");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "StepListItem", function () { return $712472d83922a2e9$export$87c2a8a94eda1754; });
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














function $712472d83922a2e9$export$87c2a8a94eda1754(props) {
    let { isDisabled: isDisabled, item: item } = props;
    let { key: key } = item;
    let ref = (0, $9ihVH$react.useRef)(null);
    let { direction: direction } = (0, $9ihVH$reactariaI18nProvider.useLocale)();
    let state = (0, $9ihVH$react.useContext)((0, $6f2b983372959298$exports.StepListContext));
    const isSelected = state.selectedKey === key;
    const isCompleted = state.isCompleted(key);
    const isItemDisabled = isDisabled || state.disabledKeys.has(key);
    let { stepProps: stepProps, stepStateProps: stepStateProps } = (0, $9ihVH$reactariaprivatesteplistuseStepListItem.useStepListItem)({
        ...props,
        key: key
    }, state, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $9ihVH$reactariauseHover.useHover)({
        ...props,
        isDisabled: isItemDisabled || isSelected || props.isReadOnly
    });
    let stepStateText = '';
    const stringFormatter = (0, $9ihVH$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($4e841e3114791c56$exports))), '@react-spectrum/steplist');
    const numberFormatter = (0, $9ihVH$reactariauseNumberFormatter.useNumberFormatter)();
    if (isSelected) stepStateText = stringFormatter.format('current');
    else if (isCompleted) stepStateText = stringFormatter.format('completed');
    else stepStateText = stringFormatter.format('notCompleted');
    let markerId = (0, $9ihVH$reactariauseId.useId)();
    let stateId = (0, $9ihVH$reactariauseId.useId)();
    let labelId = (0, $9ihVH$reactariauseId.useId)();
    return /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("li", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-item')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement((0, $9ihVH$reactariaFocusRing.FocusRing), {
        within: true,
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'focus-ring')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("a", {
        ...(0, $9ihVH$reactariamergeProps.mergeProps)(hoverProps, stepProps),
        "aria-labelledby": `${markerId} ${stateId} ${labelId}`,
        ref: ref,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-link', {
            'is-selected': isSelected && !isItemDisabled,
            'is-disabled': isItemDisabled,
            'is-hovered': isHovered,
            'is-completed': isCompleted,
            'is-selectable': state.isSelectable(key) && !isSelected
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement((0, $9ihVH$reactariaVisuallyHidden.VisuallyHidden), {
        ...stepStateProps,
        id: stateId
    }, stepStateText), /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("div", {
        id: labelId,
        "aria-hidden": "true",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-label')
    }, item.rendered), /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-segment', {
            'is-completed': isCompleted
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("svg", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-segmentLine'),
        xmlns: "http://www.w3.org/2000/svg",
        height: "100%",
        viewBox: "0 0 2 8",
        preserveAspectRatio: "none"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("line", {
        x1: "1",
        y1: "0",
        x2: "1",
        y2: "8",
        vectorEffect: "non-scaling-stroke"
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement((0, ($parcel$interopDefault($9ihVH$spectrumiconsuiChevronRightMedium))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-chevron', {
            'is-reversed': direction === 'rtl'
        })
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("div", {
        "aria-hidden": "true",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-markerWrapper')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($9ihVH$react))).createElement("div", {
        id: markerId,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist-marker')
    }, numberFormatter.format((item.index || 0) + 1))))));
}


//# sourceMappingURL=StepListItem.cjs.map
