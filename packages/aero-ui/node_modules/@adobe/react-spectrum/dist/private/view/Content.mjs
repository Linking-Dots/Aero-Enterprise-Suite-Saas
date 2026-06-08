import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $grc2g$filterDOMProps} from "react-aria/filterDOMProps";
import $grc2g$react, {forwardRef as $grc2g$forwardRef} from "react";

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




const $b579958ab95f14cb$export$7c6e2c02157bb7d2 = /*#__PURE__*/ (0, $grc2g$forwardRef)(function Content(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'content');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $grc2g$react).createElement("section", {
        ...(0, $grc2g$filterDOMProps)(otherProps),
        ...styleProps,
        ref: domRef
    }, /*#__PURE__*/ (0, $grc2g$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, children));
});


export {$b579958ab95f14cb$export$7c6e2c02157bb7d2 as Content};
//# sourceMappingURL=Content.mjs.map
