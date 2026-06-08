var $048d76b84370f141$exports = require("./utils.cjs");
var $68ctA$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $68ctA$reactariamergeProps = require("react-aria/mergeProps");
var $68ctA$react = require("react");
var $68ctA$reactariauseFocusRing = require("react-aria/useFocusRing");
var $68ctA$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "InputContext", function () { return $81dc1c05bf045ce0$export$37fb8590cf2c088c; });
$parcel$export(module.exports, "Input", function () { return $81dc1c05bf045ce0$export$f5b8910cec6cf069; });
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





const $81dc1c05bf045ce0$export$37fb8590cf2c088c = /*#__PURE__*/ (0, $68ctA$react.createContext)({});
let $81dc1c05bf045ce0$var$filterHoverProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $81dc1c05bf045ce0$export$f5b8910cec6cf069 = /*#__PURE__*/ (0, $68ctA$reactariaprivatecollectionsHidden.createHideableComponent)(function Input(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $81dc1c05bf045ce0$export$37fb8590cf2c088c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $68ctA$reactariauseHover.useHover)({
        ...props,
        isDisabled: props.disabled
    });
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $68ctA$reactariauseFocusRing.useFocusRing)({
        isTextInput: true,
        autoFocus: props.autoFocus
    });
    let isInvalid = !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.disabled || false,
            isInvalid: isInvalid
        },
        defaultClassName: 'react-aria-Input'
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($68ctA$react))).createElement((0, $048d76b84370f141$exports.dom).input, {
        ...(0, $68ctA$reactariamergeProps.mergeProps)($81dc1c05bf045ce0$var$filterHoverProps(props), focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-disabled": props.disabled || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-invalid": isInvalid || undefined
    });
});


//# sourceMappingURL=Input.cjs.map
