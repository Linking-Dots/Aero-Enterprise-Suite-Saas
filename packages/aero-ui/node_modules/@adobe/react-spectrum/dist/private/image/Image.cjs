var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../image_vars.css");
var $c29c42f99755a771$exports = require("../image_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $5VzMO$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $5VzMO$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Image", function () { return $e81b3a832cb44760$export$3e431a229df88919; });
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







const $e81b3a832cb44760$export$3e431a229df88919 = /*#__PURE__*/ (0, ($parcel$interopDefault($5VzMO$react))).forwardRef(// incomplete component for show right now
function Image(props, ref) {
    /* Slots should be able to pass an alt for default behavior, but in Images, the child may know better. */ let userProvidedAlt = props.alt;
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'image');
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { objectFit: objectFit, src: src, alt: alt, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    if (alt == null && process.env.NODE_ENV !== 'production') console.warn('The `alt` prop was not provided to an image. Add `alt` text for screen readers, or set `alt=""` prop to indicate that the image is decorative or redundant with displayed text and should not be announced by screen readers.');
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5VzMO$react))).createElement("div", {
        ...(0, $5VzMO$reactariafilterDOMProps.filterDOMProps)(props),
        ...styleProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c29c42f99755a771$exports))), styleProps.className),
        style: {
            ...styleProps.style,
            overflow: 'hidden'
        },
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5VzMO$react))).createElement("img", {
        src: src,
        alt: userProvidedAlt || alt,
        style: {
            objectFit: objectFit
        },
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c29c42f99755a771$exports))), 'spectrum-Image-img'),
        onError: props?.onError,
        onLoad: props?.onLoad,
        crossOrigin: props?.crossOrigin
    }));
});


//# sourceMappingURL=Image.cjs.map
