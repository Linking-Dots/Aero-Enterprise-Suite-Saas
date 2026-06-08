import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearSlots as $62024859ff9f1f8a$export$ceb145244332b7a2, SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686, useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {Flex as $ec3baf921918e057$export$f51f4c4ede09e011} from "../layout/Flex.mjs";
import "../illustratedmessage_vars.css";
import $4hTWy$illustratedmessage_vars_cssmjs from "../illustratedmessage_vars_css.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $4hTWy$filterDOMProps} from "react-aria/filterDOMProps";
import $4hTWy$react, {forwardRef as $4hTWy$forwardRef} from "react";


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






const $85b1778b79462345$export$406dbc84c317ece0 = /*#__PURE__*/ (0, $4hTWy$forwardRef)(function IllustratedMessage(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'illustration');
    let { children: children, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let headingClassName = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4hTWy$illustratedmessage_vars_cssmjs))), 'spectrum-IllustratedMessage-heading');
    let contentClassName = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4hTWy$illustratedmessage_vars_cssmjs))), 'spectrum-IllustratedMessage-description');
    let slots = {
        heading: {
            UNSAFE_className: headingClassName
        },
        content: {
            UNSAFE_className: contentClassName
        }
    };
    return /*#__PURE__*/ (0, $4hTWy$react).createElement((0, $ec3baf921918e057$export$f51f4c4ede09e011), {
        ...(0, $4hTWy$filterDOMProps)(otherProps),
        UNSAFE_style: styleProps.style,
        isHidden: styleProps.hidden,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4hTWy$illustratedmessage_vars_cssmjs))), 'spectrum-IllustratedMessage', styleProps.className),
        ref: ref
    }, /*#__PURE__*/ (0, $4hTWy$react).createElement((0, $62024859ff9f1f8a$export$ceb145244332b7a2), null, /*#__PURE__*/ (0, $4hTWy$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: slots
    }, children)));
});


export {$85b1778b79462345$export$406dbc84c317ece0 as IllustratedMessage};
//# sourceMappingURL=IllustratedMessage.mjs.map
