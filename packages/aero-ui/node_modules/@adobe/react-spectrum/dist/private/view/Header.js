import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $a4ctt$filterDOMProps} from "react-aria/filterDOMProps";
import $a4ctt$react, {forwardRef as $a4ctt$forwardRef} from "react";

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




const $eba69d1aae786da8$export$8b251419efc915eb = /*#__PURE__*/ (0, $a4ctt$forwardRef)(function Header(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'header');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $a4ctt$react).createElement("header", {
        ...(0, $a4ctt$filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, /*#__PURE__*/ (0, $a4ctt$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, children));
});


export {$eba69d1aae786da8$export$8b251419efc915eb as Header};
//# sourceMappingURL=Header.js.map
