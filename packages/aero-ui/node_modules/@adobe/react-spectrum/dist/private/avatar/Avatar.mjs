import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {dimensionValue as $63d03c54ca5e4b88$export$abc24f5b99744ea6, useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import "../avatar_vars.css";
import $5n4oQ$avatar_vars_cssmjs from "../avatar_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {filterDOMProps as $5n4oQ$filterDOMProps} from "react-aria/filterDOMProps";
import $5n4oQ$react, {forwardRef as $5n4oQ$forwardRef} from "react";


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







const $446d0b3ff0b2d0e3$var$DEFAULT_SIZE = 'avatar-size-100';
const $446d0b3ff0b2d0e3$var$SIZE_RE = /^size-\d+/;
const $446d0b3ff0b2d0e3$export$e2255cf6045e8d47 = /*#__PURE__*/ (0, $5n4oQ$forwardRef)(function Avatar(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'avatar');
    const { alt: alt = '', isDisabled: isDisabled, size: size = $446d0b3ff0b2d0e3$var$DEFAULT_SIZE, src: src, ...otherProps } = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    const { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    const domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    const domProps = (0, $5n4oQ$filterDOMProps)(otherProps);
    // Casting `size` as `any` since `isNaN` expects a `number`, but we want it
    // to handle `string` numbers; e.g. '300' as opposed to 300
    const sizeValue = typeof size !== 'number' && ($446d0b3ff0b2d0e3$var$SIZE_RE.test(size) || !isNaN(size)) ? (0, $63d03c54ca5e4b88$export$abc24f5b99744ea6)($446d0b3ff0b2d0e3$var$DEFAULT_SIZE) // override disallowed size values
     : (0, $63d03c54ca5e4b88$export$abc24f5b99744ea6)(size || $446d0b3ff0b2d0e3$var$DEFAULT_SIZE);
    return /*#__PURE__*/ (0, $5n4oQ$react).createElement("img", {
        ...styleProps,
        ...domProps,
        alt: alt,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($5n4oQ$avatar_vars_cssmjs))), 'spectrum-Avatar', {
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


export {$446d0b3ff0b2d0e3$export$e2255cf6045e8d47 as Avatar};
//# sourceMappingURL=Avatar.mjs.map
