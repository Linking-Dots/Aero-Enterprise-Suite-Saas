import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {filterDOMProps as $cVyXW$filterDOMProps} from "react-aria/filterDOMProps";
import $cVyXW$react, {createContext as $cVyXW$createContext, forwardRef as $cVyXW$forwardRef} from "react";

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


const $4fcfe18fac72dabd$export$2de4954e8ae13b9f = /*#__PURE__*/ (0, $cVyXW$createContext)({
    placement: 'bottom'
});
const $4fcfe18fac72dabd$export$746d02f47f4d381 = /*#__PURE__*/ (0, $cVyXW$forwardRef)(function OverlayArrow(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $4fcfe18fac72dabd$export$2de4954e8ae13b9f);
    let placement = props.placement;
    let style = {
        position: 'absolute',
        transform: placement === 'top' || placement === 'bottom' ? 'translateX(-50%)' : 'translateY(-50%)'
    };
    if (placement != null) style[placement] = '100%';
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-OverlayArrow',
        values: {
            placement: placement
        }
    });
    // remove undefined values from renderProps.style object so that it can be
    // spread merged with the other style object
    if (renderProps.style) Object.keys(renderProps.style).forEach((key)=>renderProps.style[key] === undefined && delete renderProps.style[key]);
    let DOMProps = (0, $cVyXW$filterDOMProps)(props);
    return /*#__PURE__*/ (0, $cVyXW$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
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


export {$4fcfe18fac72dabd$export$2de4954e8ae13b9f as OverlayArrowContext, $4fcfe18fac72dabd$export$746d02f47f4d381 as OverlayArrow};
//# sourceMappingURL=OverlayArrow.mjs.map
