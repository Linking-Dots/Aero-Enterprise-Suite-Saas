var $048d76b84370f141$exports = require("./utils.cjs");
var $egeOG$reactariauseLink = require("react-aria/useLink");
var $egeOG$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $egeOG$reactariamergeProps = require("react-aria/mergeProps");
var $egeOG$react = require("react");
var $egeOG$reactariauseFocusRing = require("react-aria/useFocusRing");
var $egeOG$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "LinkContext", function () { return $993df839da838aaa$export$e2509388b49734e7; });
$parcel$export(module.exports, "Link", function () { return $993df839da838aaa$export$a6c7ac8248d6e38a; });
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






const $993df839da838aaa$export$e2509388b49734e7 = /*#__PURE__*/ (0, $egeOG$react.createContext)(null);
const $993df839da838aaa$export$a6c7ac8248d6e38a = /*#__PURE__*/ (0, $egeOG$react.forwardRef)(function Link(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $993df839da838aaa$export$e2509388b49734e7);
    let elementType = props.href && !props.isDisabled ? 'a' : 'span';
    let { linkProps: linkProps, isPressed: isPressed } = (0, $egeOG$reactariauseLink.useLink)({
        ...props,
        elementType: elementType
    }, ref);
    let ElementType = (0, $048d76b84370f141$exports.dom)[elementType];
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $egeOG$reactariauseHover.useHover)(props);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $egeOG$reactariauseFocusRing.useFocusRing)();
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-Link',
        values: {
            isCurrent: !!props['aria-current'],
            isDisabled: props.isDisabled || false,
            isPressed: isPressed,
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible
        }
    });
    let DOMProps = (0, $egeOG$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($egeOG$react))).createElement(ElementType, {
        ref: ref,
        slot: props.slot || undefined,
        ...(0, $egeOG$reactariamergeProps.mergeProps)(DOMProps, renderProps, linkProps, hoverProps, focusProps),
        "data-focused": isFocused || undefined,
        "data-hovered": isHovered || undefined,
        "data-pressed": isPressed || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-current": !!props['aria-current'] || undefined,
        "data-disabled": props.isDisabled || undefined
    }, renderProps.children);
});


//# sourceMappingURL=Link.cjs.map
