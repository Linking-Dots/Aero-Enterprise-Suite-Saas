import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {dimensionValue as $120fbea2d95e11ed$export$abc24f5b99744ea6, useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import "../avatar_vars.css";
import $bAQWh$avatar_vars_cssmjs from "../avatar_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {filterDOMProps as $bAQWh$filterDOMProps} from "react-aria/filterDOMProps";
import $bAQWh$react, {forwardRef as $bAQWh$forwardRef} from "react";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2021 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 







const $e11bc02d3528b12b$var$DEFAULT_SIZE = 'avatar-size-100';
const $e11bc02d3528b12b$var$SIZE_RE = /^size-\d+/;
const $e11bc02d3528b12b$export$e2255cf6045e8d47 = /*#__PURE__*/ (0, $bAQWh$forwardRef)(function Avatar(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'avatar');
    const { alt: alt = '', isDisabled: isDisabled, size: size = $e11bc02d3528b12b$var$DEFAULT_SIZE, src: src, ...otherProps } = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    const { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    const domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    const domProps = (0, $bAQWh$filterDOMProps)(otherProps);
    // Casting `size` as `any` since `isNaN` expects a `number`, but we want it
    // to handle `string` numbers; e.g. '300' as opposed to 300
    const sizeValue = typeof size !== 'number' && ($e11bc02d3528b12b$var$SIZE_RE.test(size) || !isNaN(size)) ? (0, $120fbea2d95e11ed$export$abc24f5b99744ea6)($e11bc02d3528b12b$var$DEFAULT_SIZE) // override disallowed size values
     : (0, $120fbea2d95e11ed$export$abc24f5b99744ea6)(size || $e11bc02d3528b12b$var$DEFAULT_SIZE);
    return /*#__PURE__*/ (0, $bAQWh$react).createElement("img", {
        ...styleProps,
        ...domProps,
        alt: alt,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($bAQWh$avatar_vars_cssmjs))), 'spectrum-Avatar', {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef,
        src: src,
        style: {
            ...styleProps.style,
            ...sizeValue && {
                height: sizeValue,
                width: sizeValue
            }
        }
    });
});


export {$e11bc02d3528b12b$export$e2255cf6045e8d47 as Avatar};
//# sourceMappingURL=Avatar.js.map
