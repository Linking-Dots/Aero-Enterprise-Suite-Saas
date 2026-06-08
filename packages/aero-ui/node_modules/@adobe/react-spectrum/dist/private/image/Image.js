import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../image_vars.css";
import $bP99f$image_vars_cssmjs from "../image_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $bP99f$filterDOMProps} from "react-aria/filterDOMProps";
import $bP99f$react from "react";


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







const $8d4f5af52a313241$export$3e431a229df88919 = /*#__PURE__*/ (0, $bP99f$react).forwardRef(// incomplete component for show right now
function Image(props, ref) {
    /* Slots should be able to pass an alt for default behavior, but in Images, the child may know better. */ let userProvidedAlt = props.alt;
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'image');
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { objectFit: objectFit, src: src, alt: alt, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    if (alt == null && process.env.NODE_ENV !== 'production') console.warn('The `alt` prop was not provided to an image. Add `alt` text for screen readers, or set `alt=""` prop to indicate that the image is decorative or redundant with displayed text and should not be announced by screen readers.');
    return /*#__PURE__*/ (0, $bP99f$react).createElement("div", {
        ...(0, $bP99f$filterDOMProps)(props),
        ...styleProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bP99f$image_vars_cssmjs))), styleProps.className),
        style: {
            ...styleProps.style,
            overflow: 'hidden'
        },
        ref: domRef
    }, /*#__PURE__*/ (0, $bP99f$react).createElement("img", {
        src: src,
        alt: userProvidedAlt || alt,
        style: {
            objectFit: objectFit
        },
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bP99f$image_vars_cssmjs))), 'spectrum-Image-img'),
        onError: props === null || props === void 0 ? void 0 : props.onError,
        onLoad: props === null || props === void 0 ? void 0 : props.onLoad,
        crossOrigin: props === null || props === void 0 ? void 0 : props.crossOrigin
    }));
});


export {$8d4f5af52a313241$export$3e431a229df88919 as Image};
//# sourceMappingURL=Image.js.map
