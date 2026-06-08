var $048d76b84370f141$exports = require("./utils.cjs");
var $71mAU$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $71mAU$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "OverlayArrowContext", function () { return $89c727392dd0ab84$export$2de4954e8ae13b9f; });
$parcel$export(module.exports, "OverlayArrow", function () { return $89c727392dd0ab84$export$746d02f47f4d381; });
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


const $89c727392dd0ab84$export$2de4954e8ae13b9f = /*#__PURE__*/ (0, $71mAU$react.createContext)({
    placement: 'bottom'
});
const $89c727392dd0ab84$export$746d02f47f4d381 = /*#__PURE__*/ (0, $71mAU$react.forwardRef)(function OverlayArrow(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $89c727392dd0ab84$export$2de4954e8ae13b9f);
    let placement = props.placement;
    let style = {
        position: 'absolute',
        transform: placement === 'top' || placement === 'bottom' ? 'translateX(-50%)' : 'translateY(-50%)'
    };
    if (placement != null) style[placement] = '100%';
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-OverlayArrow',
        values: {
            placement: placement
        }
    });
    // remove undefined values from renderProps.style object so that it can be
    // spread merged with the other style object
    if (renderProps.style) Object.keys(renderProps.style).forEach((key)=>renderProps.style[key] === undefined && delete renderProps.style[key]);
    let DOMProps = (0, $71mAU$reactariafilterDOMProps.filterDOMProps)(props);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($71mAU$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        style: {
            ...style,
            ...renderProps.style
        },
        ref: ref,
        "data-placement": placement
    });
});


//# sourceMappingURL=OverlayArrow.cjs.map
