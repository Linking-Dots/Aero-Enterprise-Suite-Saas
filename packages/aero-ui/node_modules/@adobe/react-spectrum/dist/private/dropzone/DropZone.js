import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import $lzzda$intlStringsjs from "./intlStrings.js";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import "../dropzone_vars.css";
import $lzzda$dropzone_vars_cssmjs from "../dropzone_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {DropZone as $lzzda$DropZone} from "react-aria-components/DropZone";
import {HeadingContext as $lzzda$HeadingContext} from "react-aria-components/Heading";
import {mergeProps as $lzzda$mergeProps} from "react-aria/mergeProps";
import {Provider as $lzzda$Provider} from "react-aria-components/slots";
import $lzzda$react from "react";
import {useId as $lzzda$useId} from "react-aria/useId";
import {useLocalizedStringFormatter as $lzzda$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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
let $9b7e00ab669c9f2e$var$filterProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $9b7e00ab669c9f2e$export$3c6489d84dc98b6 = /*#__PURE__*/ (0, $lzzda$react).forwardRef(function DropZone(props, ref) {
    let { children: children, isFilled: isFilled, replaceMessage: replaceMessage, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let messageId = (0, $lzzda$useId)();
    let headingId = (0, $lzzda$useId)();
    let stringFormatter = (0, $lzzda$useLocalizedStringFormatter)((0, ($parcel$interopDefault($lzzda$intlStringsjs))), '@react-spectrum/dropzone');
    let ariaLabelledby = isFilled ? `${headingId} ${messageId}` : headingId;
    return /*#__PURE__*/ (0, $lzzda$react).createElement((0, $lzzda$Provider), {
        values: [
            [
                (0, $lzzda$HeadingContext),
                {
                    id: headingId
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $lzzda$react).createElement((0, $lzzda$DropZone), {
        ...(0, $lzzda$mergeProps)($9b7e00ab669c9f2e$var$filterProps(otherProps)),
        ...styleProps,
        "aria-labelledby": ariaLabelledby,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lzzda$dropzone_vars_cssmjs))), 'spectrum-Dropzone', styleProps.className, {
            'spectrum-Dropzone--filled': isFilled
        }),
        ref: domRef
    }, /*#__PURE__*/ (0, $lzzda$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            illustration: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lzzda$dropzone_vars_cssmjs))), 'spectrum-Dropzone-illustratedMessage')
            }
        }
    }, children), /*#__PURE__*/ (0, $lzzda$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lzzda$dropzone_vars_cssmjs))), 'spectrum-Dropzone-backdrop')
    }), /*#__PURE__*/ (0, $lzzda$react).createElement("div", {
        id: messageId,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lzzda$dropzone_vars_cssmjs))), 'spectrum-Dropzone-banner', styleProps.className)
    }, replaceMessage ? replaceMessage : stringFormatter.format('replaceMessage'))));
});


export {$9b7e00ab669c9f2e$export$3c6489d84dc98b6 as DropZone};
//# sourceMappingURL=DropZone.js.map
