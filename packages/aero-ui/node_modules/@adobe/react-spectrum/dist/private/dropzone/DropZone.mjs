import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import $kvp1q$intlStringsmjs from "./intlStrings.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../dropzone_vars.css";
import $kvp1q$dropzone_vars_cssmjs from "../dropzone_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {DropZone as $kvp1q$DropZone} from "react-aria-components/DropZone";
import {HeadingContext as $kvp1q$HeadingContext} from "react-aria-components/Heading";
import {mergeProps as $kvp1q$mergeProps} from "react-aria/mergeProps";
import {Provider as $kvp1q$Provider} from "react-aria-components/slots";
import $kvp1q$react from "react";
import {useId as $kvp1q$useId} from "react-aria/useId";
import {useLocalizedStringFormatter as $kvp1q$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 












// Filter out props used by RAC DropZone that we don't want in RSP DropZone for now.
let $96f10c5d34ec1f07$var$filterProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $96f10c5d34ec1f07$export$3c6489d84dc98b6 = /*#__PURE__*/ (0, $kvp1q$react).forwardRef(function DropZone(props, ref) {
    let { children: children, isFilled: isFilled, replaceMessage: replaceMessage, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let messageId = (0, $kvp1q$useId)();
    let headingId = (0, $kvp1q$useId)();
    let stringFormatter = (0, $kvp1q$useLocalizedStringFormatter)((0, ($parcel$interopDefault($kvp1q$intlStringsmjs))), '@react-spectrum/dropzone');
    let ariaLabelledby = isFilled ? `${headingId} ${messageId}` : headingId;
    return /*#__PURE__*/ (0, $kvp1q$react).createElement((0, $kvp1q$Provider), {
        values: [
            [
                (0, $kvp1q$HeadingContext),
                {
                    id: headingId
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $kvp1q$react).createElement((0, $kvp1q$DropZone), {
        ...(0, $kvp1q$mergeProps)($96f10c5d34ec1f07$var$filterProps(otherProps)),
        ...styleProps,
        "aria-labelledby": ariaLabelledby,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kvp1q$dropzone_vars_cssmjs))), 'spectrum-Dropzone', styleProps.className, {
            'spectrum-Dropzone--filled': isFilled
        }),
        ref: domRef
    }, /*#__PURE__*/ (0, $kvp1q$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            illustration: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kvp1q$dropzone_vars_cssmjs))), 'spectrum-Dropzone-illustratedMessage')
            }
        }
    }, children), /*#__PURE__*/ (0, $kvp1q$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kvp1q$dropzone_vars_cssmjs))), 'spectrum-Dropzone-backdrop')
    }), /*#__PURE__*/ (0, $kvp1q$react).createElement("div", {
        id: messageId,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kvp1q$dropzone_vars_cssmjs))), 'spectrum-Dropzone-banner', styleProps.className)
    }, replaceMessage ? replaceMessage : stringFormatter.format('replaceMessage'))));
});


export {$96f10c5d34ec1f07$export$3c6489d84dc98b6 as DropZone};
//# sourceMappingURL=DropZone.mjs.map
