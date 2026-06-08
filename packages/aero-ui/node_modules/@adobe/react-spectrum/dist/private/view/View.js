import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41, viewStyleProps as $120fbea2d95e11ed$export$e0705d1a55f297c} from "../utils/styleProps.js";
import {filterDOMProps as $kgpFU$filterDOMProps} from "react-aria/filterDOMProps";
import $kgpFU$react, {forwardRef as $kgpFU$forwardRef} from "react";

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




const $04bf23ef16a59f4a$export$27a5bd065ad55220 = /*#__PURE__*/ (0, $kgpFU$forwardRef)(function View(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props);
    let { elementType: ElementType = 'div', children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props, (0, $120fbea2d95e11ed$export$e0705d1a55f297c));
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $kgpFU$react).createElement(ElementType, {
        ...(0, $kgpFU$filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, /*#__PURE__*/ (0, $kgpFU$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, children));
});


export {$04bf23ef16a59f4a$export$27a5bd065ad55220 as View};
//# sourceMappingURL=View.js.map
