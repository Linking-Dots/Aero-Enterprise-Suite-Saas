import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../image_vars.css";
import $3jejY$image_vars_cssmjs from "../image_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $3jejY$filterDOMProps} from "react-aria/filterDOMProps";
import $3jejY$react from "react";


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







const $cff5e59729b4d578$export$3e431a229df88919 = /*#__PURE__*/ (0, $3jejY$react).forwardRef(// incomplete component for show right now
function Image(props, ref) {
    /* Slots should be able to pass an alt for default behavior, but in Images, the child may know better. */ let userProvidedAlt = props.alt;
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'image');
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { objectFit: objectFit, src: src, alt: alt, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    if (alt == null && process.env.NODE_ENV !== 'production') console.warn('The `alt` prop was not provided to an image. Add `alt` text for screen readers, or set `alt=""` prop to indicate that the image is decorative or redundant with displayed text and should not be announced by screen readers.');
    return /*#__PURE__*/ (0, $3jejY$react).createElement("div", {
        ...(0, $3jejY$filterDOMProps)(props),
        ...styleProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3jejY$image_vars_cssmjs))), styleProps.className),
        style: {
            ...styleProps.style,
            overflow: 'hidden'
        },
        ref: domRef
    }, /*#__PURE__*/ (0, $3jejY$react).createElement("img", {
        src: src,
        alt: userProvidedAlt || alt,
        style: {
            objectFit: objectFit
        },
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3jejY$image_vars_cssmjs))), 'spectrum-Image-img'),
        onError: props?.onError,
        onLoad: props?.onLoad,
        crossOrigin: props?.crossOrigin
    }));
});


export {$cff5e59729b4d578$export$3e431a229df88919 as Image};
//# sourceMappingURL=Image.mjs.map
