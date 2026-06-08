import "./ColorPicker.css";
import {ColorSwatch as $dbd0cc077b352deb$export$cae13e90592f246a} from "./ColorSwatch.mjs";
import {Content as $b579958ab95f14cb$export$7c6e2c02157bb7d2} from "../view/Content.mjs";
import {Dialog as $8054558191a4f1c9$export$3ddf2d174ce01153} from "../dialog/Dialog.mjs";
import {DialogTrigger as $41e0ad2a982a34c5$export$2e1e1122cf0cba88} from "../dialog/DialogTrigger.mjs";
import {unwrapDOMRef as $3c2c983d5210446c$export$c7e28c72a4823176, useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {ColorPicker as $60jNb$ColorPicker} from "react-aria-components/ColorPicker";
import {Button as $60jNb$Button} from "react-aria-components/Button";
import $60jNb$react, {useRef as $60jNb$useRef} from "react";
import {useId as $60jNb$useId} from "react-aria/useId";

/*
 * Copyright 2024 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 








const $b219da6141192129$export$9feb1bc2e5f1ccb3 = /*#__PURE__*/ (0, $60jNb$react).forwardRef(function ColorPicker(props, ref) {
    let swatchRef = (0, $60jNb$useRef)(null);
    let domRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref);
    let labelId = (0, $60jNb$useId)();
    return /*#__PURE__*/ (0, $60jNb$react).createElement((0, $60jNb$ColorPicker), props, /*#__PURE__*/ (0, $60jNb$react).createElement((0, $41e0ad2a982a34c5$export$2e1e1122cf0cba88), {
        type: "popover",
        mobileType: "tray",
        targetRef: (0, $3c2c983d5210446c$export$c7e28c72a4823176)(swatchRef)
    }, /*#__PURE__*/ (0, $60jNb$react).createElement((0, $60jNb$Button), {
        ref: domRef,
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-bs1-a';
            rules += ' s1-As1-f';
            rules += ' s1-Is1-a';
            rules += ' s1-Js1-a';
            rules += ' s1-Gs1-a';
            rules += ' s1-Hs1-a';
            rules += ' s1-_Ts1-d';
            rules += ' s1-_Vs1-c';
            rules += ' s1-ls1-M';
            rules += ' s1-ms1-M';
            rules += ' s1-_Fs1-a';
            rules += ' s1-5-bc1l9os1-h';
            rules += ' s1-5-1uotwbws1-g';
            rules += ' s1-5-eo0c6ss1-f';
            rules += ' s1-5-enzzrgs1-e';
            rules += ' s1-5-enzykds1-d';
            rules += ' s1-5-enzwzjs1-c';
            rules += ' s1-5-enzrfps1-b';
            rules += ' s1-5s1-a';
            rules += ' s1-as1-___K';
            if (props.size === "L") rules += ' s1-6s1-d';
            else if (props.size === "M") rules += ' s1-6s1-c';
            else if (props.size === "S") rules += ' s1-6s1-b';
            else if (props.size === "XS") rules += ' s1-6s1-a';
            return rules;
        }({
            size: props.size || 'M'
        })
    }, ({ isFocusVisible: isFocusVisible })=>/*#__PURE__*/ (0, $60jNb$react).createElement((0, $60jNb$react).Fragment, null, /*#__PURE__*/ (0, $60jNb$react).createElement("div", {
            className: function anonymous(props) {
                let rules = "";
                if (props.isFocusVisible) rules += ' s1-_Fs1-b';
                else rules += ' s1-_Fs1-a';
                rules += ' s1-ds1-as1-___D';
                rules += ' s1-ds1-___I';
                rules += ' s1-_Hs1-c';
                rules += ' s1-_Gs1-c';
                rules += ' s1-_qs1-c';
                rules += ' s1-_rs1-c';
                rules += ' s1-_ss1-c';
                rules += ' s1-_ts1-c';
                return rules;
            }({
                isFocusVisible: isFocusVisible
            })
        }, /*#__PURE__*/ (0, $60jNb$react).createElement((0, $dbd0cc077b352deb$export$cae13e90592f246a), {
            ref: swatchRef,
            color: props.value,
            size: props.size,
            rounding: props.rounding,
            "aria-label": props['aria-label'],
            "aria-labelledby": props['aria-labelledby'],
            "aria-describedby": props['aria-describedby'],
            "aria-details": props['aria-details']
        })), props.label && /*#__PURE__*/ (0, $60jNb$react).createElement("span", {
            id: labelId
        }, props.label))), /*#__PURE__*/ (0, $60jNb$react).createElement((0, $8054558191a4f1c9$export$3ddf2d174ce01153), {
        "aria-labelledby": props.label ? labelId : props['aria-labelledby'],
        "aria-label": props['aria-label'],
        UNSAFE_style: {
            width: 'fit-content',
            minWidth: 0,
            margin: '0 auto' // Center within tray.
        }
    }, /*#__PURE__*/ (0, $60jNb$react).createElement((0, $b579958ab95f14cb$export$7c6e2c02157bb7d2), {
        UNSAFE_style: {
            position: 'relative',
            margin: 'calc(var(--spectrum-dialog-padding) * -1)',
            padding: 'var(--spectrum-global-dimension-size-200)'
        }
    }, props.children))));
});


export {$b219da6141192129$export$9feb1bc2e5f1ccb3 as ColorPicker};
//# sourceMappingURL=ColorPicker.mjs.map
