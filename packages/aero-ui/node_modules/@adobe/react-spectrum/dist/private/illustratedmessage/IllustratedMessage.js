import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearSlots as $68f4bc2c1abc5618$export$ceb145244332b7a2, SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686, useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {Flex as $9b6884c982c0954a$export$f51f4c4ede09e011} from "../layout/Flex.js";
import "../illustratedmessage_vars.css";
import $xKAKP$illustratedmessage_vars_cssmjs from "../illustratedmessage_vars_css.mjs";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $xKAKP$filterDOMProps} from "react-aria/filterDOMProps";
import $xKAKP$react, {forwardRef as $xKAKP$forwardRef} from "react";


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






const $0c0ec59e9de032cd$export$406dbc84c317ece0 = /*#__PURE__*/ (0, $xKAKP$forwardRef)(function IllustratedMessage(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'illustration');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let headingClassName = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($xKAKP$illustratedmessage_vars_cssmjs))), 'spectrum-IllustratedMessage-heading');
    let contentClassName = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($xKAKP$illustratedmessage_vars_cssmjs))), 'spectrum-IllustratedMessage-description');
    let slots = {
        heading: {
            UNSAFE_className: headingClassName
        },
        content: {
            UNSAFE_className: contentClassName
        }
    };
    return /*#__PURE__*/ (0, $xKAKP$react).createElement((0, $9b6884c982c0954a$export$f51f4c4ede09e011), {
        ...(0, $xKAKP$filterDOMProps)(otherProps),
        UNSAFE_style: styleProps.style,
        isHidden: styleProps.hidden,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($xKAKP$illustratedmessage_vars_cssmjs))), 'spectrum-IllustratedMessage', styleProps.className),
        ref: ref
    }, /*#__PURE__*/ (0, $xKAKP$react).createElement((0, $68f4bc2c1abc5618$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $xKAKP$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: slots
    }, children)));
});


export {$0c0ec59e9de032cd$export$406dbc84c317ece0 as IllustratedMessage};
//# sourceMappingURL=IllustratedMessage.js.map
