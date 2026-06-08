import "./ColorPicker.css";
import {ColorSwatch as $471078cf7ddd2506$export$cae13e90592f246a} from "./ColorSwatch.js";
import {Content as $558e2ad48297783c$export$7c6e2c02157bb7d2} from "../view/Content.js";
import {Dialog as $89418a3659cad0c7$export$3ddf2d174ce01153} from "../dialog/Dialog.js";
import {DialogTrigger as $bcff05049955156f$export$2e1e1122cf0cba88} from "../dialog/DialogTrigger.js";
import {unwrapDOMRef as $c234463e9ef56637$export$c7e28c72a4823176, useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {ColorPicker as $uRFeS$ColorPicker} from "react-aria-components/ColorPicker";
import {Button as $uRFeS$Button} from "react-aria-components/Button";
import $uRFeS$react, {useRef as $uRFeS$useRef} from "react";
import {useId as $uRFeS$useId} from "react-aria/useId";

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








const $c04167cb12cd5428$export$9feb1bc2e5f1ccb3 = /*#__PURE__*/ (0, $uRFeS$react).forwardRef(function ColorPicker(props, ref) {
    let swatchRef = (0, $uRFeS$useRef)(null);
    let domRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref);
    let labelId = (0, $uRFeS$useId)();
    return /*#__PURE__*/ (0, $uRFeS$react).createElement((0, $uRFeS$ColorPicker), props, /*#__PURE__*/ (0, $uRFeS$react).createElement((0, $bcff05049955156f$export$2e1e1122cf0cba88), {
        type: "popover",
        mobileType: "tray",
        targetRef: (0, $c234463e9ef56637$export$c7e28c72a4823176)(swatchRef)
    }, /*#__PURE__*/ (0, $uRFeS$react).createElement((0, $uRFeS$Button), {
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
    }, ({ isFocusVisible: isFocusVisible })=>/*#__PURE__*/ (0, $uRFeS$react).createElement((0, $uRFeS$react).Fragment, null, /*#__PURE__*/ (0, $uRFeS$react).createElement("div", {
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
        }, /*#__PURE__*/ (0, $uRFeS$react).createElement((0, $471078cf7ddd2506$export$cae13e90592f246a), {
            ref: swatchRef,
            color: props.value,
            size: props.size,
            rounding: props.rounding,
            "aria-label": props['aria-label'],
            "aria-labelledby": props['aria-labelledby'],
            "aria-describedby": props['aria-describedby'],
            "aria-details": props['aria-details']
        })), props.label && /*#__PURE__*/ (0, $uRFeS$react).createElement("span", {
            id: labelId
        }, props.label))), /*#__PURE__*/ (0, $uRFeS$react).createElement((0, $89418a3659cad0c7$export$3ddf2d174ce01153), {
        "aria-labelledby": props.label ? labelId : props['aria-labelledby'],
        "aria-label": props['aria-label'],
        UNSAFE_style: {
            width: 'fit-content',
            minWidth: 0,
            margin: '0 auto' // Center within tray.
        }
    }, /*#__PURE__*/ (0, $uRFeS$react).createElement((0, $558e2ad48297783c$export$7c6e2c02157bb7d2), {
        UNSAFE_style: {
            position: 'relative',
            margin: 'calc(var(--spectrum-dialog-padding) * -1)',
            padding: 'var(--spectrum-global-dimension-size-200)'
        }
    }, props.children))));
});


export {$c04167cb12cd5428$export$9feb1bc2e5f1ccb3 as ColorPicker};
//# sourceMappingURL=ColorPicker.js.map
