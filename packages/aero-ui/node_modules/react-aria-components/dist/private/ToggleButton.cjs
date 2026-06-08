var $048d76b84370f141$exports = require("./utils.cjs");
var $61557b2a9b2862a8$exports = require("./SelectionIndicator.cjs");
var $1a5ce205589a38e1$exports = require("./ToggleButtonGroup.cjs");
var $glVcY$reactariauseToggleButton = require("react-aria/useToggleButton");
var $glVcY$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $glVcY$reactariamergeProps = require("react-aria/mergeProps");
var $glVcY$react = require("react");
var $glVcY$reactstatelyuseToggleState = require("react-stately/useToggleState");
var $glVcY$reactariauseFocusRing = require("react-aria/useFocusRing");
var $glVcY$reactariauseHover = require("react-aria/useHover");
var $glVcY$reactariauseToggleButtonGroup = require("react-aria/useToggleButtonGroup");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ToggleButtonContext", function () { return $ac0c83b8db09e274$export$43506d75ebd2e218; });
$parcel$export(module.exports, "ToggleButton", function () { return $ac0c83b8db09e274$export$d2b052e7b4be1756; });
/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 










const $ac0c83b8db09e274$export$43506d75ebd2e218 = /*#__PURE__*/ (0, $glVcY$react.createContext)({});
const $ac0c83b8db09e274$export$d2b052e7b4be1756 = /*#__PURE__*/ (0, $glVcY$react.forwardRef)(function ToggleButton(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $ac0c83b8db09e274$export$43506d75ebd2e218);
    let groupState = (0, $glVcY$react.useContext)((0, $1a5ce205589a38e1$exports.ToggleGroupStateContext));
    let state = (0, $glVcY$reactstatelyuseToggleState.useToggleState)(groupState && props.id != null ? {
        isSelected: groupState.selectedKeys.has(props.id),
        onChange (isSelected) {
            groupState.setSelected(props.id, isSelected);
        }
    } : props);
    let { buttonProps: buttonProps, isPressed: isPressed, isSelected: isSelected, isDisabled: isDisabled } = groupState && props.id != null ? (0, $glVcY$reactariauseToggleButtonGroup.useToggleButtonGroupItem)({
        ...props,
        id: props.id
    }, groupState, ref) : (0, $glVcY$reactariauseToggleButton.useToggleButton)({
        ...props,
        id: props.id != null ? String(props.id) : undefined
    }, state, ref);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $glVcY$reactariauseFocusRing.useFocusRing)(props);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $glVcY$reactariauseHover.useHover)({
        ...props,
        isDisabled: isDisabled
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        id: undefined,
        values: {
            isHovered: isHovered,
            isPressed: isPressed,
            isFocused: isFocused,
            isSelected: state.isSelected,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ToggleButton'
    });
    let DOMProps = (0, $glVcY$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($glVcY$react))).createElement((0, $048d76b84370f141$exports.dom).button, {
        ...(0, $glVcY$reactariamergeProps.mergeProps)(DOMProps, renderProps, buttonProps, focusProps, hoverProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": isFocused || undefined,
        "data-disabled": isDisabled || undefined,
        "data-pressed": isPressed || undefined,
        "data-selected": isSelected || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($glVcY$react))).createElement((0, $61557b2a9b2862a8$exports.SelectionIndicatorContext).Provider, {
        value: {
            isSelected: isSelected
        }
    }, renderProps.children));
});


//# sourceMappingURL=ToggleButton.cjs.map
