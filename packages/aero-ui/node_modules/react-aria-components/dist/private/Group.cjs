var $048d76b84370f141$exports = require("./utils.cjs");
var $eTSRR$reactariauseHover = require("react-aria/useHover");
var $eTSRR$reactariamergeProps = require("react-aria/mergeProps");
var $eTSRR$react = require("react");
var $eTSRR$reactariauseFocusRing = require("react-aria/useFocusRing");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "GroupContext", function () { return $f3068c15cd7dcac2$export$f9c6924e160136d1; });
$parcel$export(module.exports, "Group", function () { return $f3068c15cd7dcac2$export$eb2fcfdbd7ba97d4; });
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




const $f3068c15cd7dcac2$export$f9c6924e160136d1 = /*#__PURE__*/ (0, $eTSRR$react.createContext)({});
const $f3068c15cd7dcac2$export$eb2fcfdbd7ba97d4 = /*#__PURE__*/ (0, $eTSRR$react.forwardRef)(function Group(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $f3068c15cd7dcac2$export$f9c6924e160136d1);
    let { isDisabled: isDisabled, isInvalid: isInvalid, isReadOnly: isReadOnly, onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    isDisabled ??= !!props['aria-disabled'] && props['aria-disabled'] !== 'false';
    isInvalid ??= !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $eTSRR$reactariauseHover.useHover)({
        onHoverStart: onHoverStart,
        onHoverChange: onHoverChange,
        onHoverEnd: onHoverEnd,
        isDisabled: isDisabled
    });
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $eTSRR$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            isHovered: isHovered,
            isFocusWithin: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled,
            isInvalid: isInvalid
        },
        defaultClassName: 'react-aria-Group'
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($eTSRR$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $eTSRR$reactariamergeProps.mergeProps)(otherProps, focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        role: props.role ?? 'group',
        slot: props.slot ?? undefined,
        "data-focus-within": isFocused || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined,
        "data-invalid": isInvalid || undefined,
        "data-readonly": isReadOnly || undefined
    }, renderProps.children);
});


//# sourceMappingURL=Group.cjs.map
