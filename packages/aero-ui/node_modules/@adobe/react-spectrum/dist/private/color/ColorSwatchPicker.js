import "./ColorSwatchPicker.css";
import {SpectrumColorSwatchContext as $471078cf7ddd2506$export$8529d7908a78c058} from "./ColorSwatch.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {ColorSwatchPicker as $1Jad0$ColorSwatchPicker, ColorSwatchPickerItem as $1Jad0$ColorSwatchPickerItem} from "react-aria-components/ColorSwatchPicker";
import $1Jad0$react, {forwardRef as $1Jad0$forwardRef} from "react";

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




const $7e1c53aba5966059$export$b46792416e3d8515 = /*#__PURE__*/ (0, $1Jad0$forwardRef)(function ColorSwatchPicker(props, ref) {
    let { density: density = 'regular', size: size = 'M', rounding: rounding = 'none', ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $1Jad0$react).createElement((0, $1Jad0$ColorSwatchPicker), {
        ...otherProps,
        style: styleProps.style,
        ref: domRef,
        className: styleProps.className + function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-_1s1-a';
            if (props.density === "spacious") rules += ' s1-ls1-c';
            else if (props.density === "regular") rules += ' s1-ls1-b';
            else if (props.density === "compact") rules += ' s1-ls1-F';
            if (props.density === "spacious") rules += ' s1-ms1-c';
            else if (props.density === "regular") rules += ' s1-ms1-b';
            else if (props.density === "compact") rules += ' s1-ms1-F';
            return rules;
        }({
            density: density
        })
    }, /*#__PURE__*/ (0, $1Jad0$react).createElement((0, $471078cf7ddd2506$export$8529d7908a78c058).Provider, {
        value: {
            useWrapper: $7e1c53aba5966059$var$useWrapper,
            size: size,
            rounding: rounding
        }
    }, props.children));
});
function $7e1c53aba5966059$var$useWrapper(swatch, color, rounding) {
    return /*#__PURE__*/ (0, $1Jad0$react).createElement((0, $1Jad0$ColorSwatchPickerItem), {
        color: color,
        className: (renderProps)=>(function anonymous(props) {
                let rules = "";
                if (props.isFocusVisible) rules += ' s1-_Fs1-b';
                else rules += ' s1-_Fs1-a';
                rules += ' s1-ds1-as1-___D';
                rules += ' s1-ds1-___I';
                rules += ' s1-_Hs1-c';
                rules += ' s1-_Gs1-c';
                rules += ' s1-Xs1-c';
                if (props.rounding === "full") rules += ' s1-_qs1-f';
                else if (props.rounding === "default") rules += ' s1-_qs1-c';
                else if (props.rounding === "none") rules += ' s1-_qs1-a';
                if (props.rounding === "full") rules += ' s1-_rs1-f';
                else if (props.rounding === "default") rules += ' s1-_rs1-c';
                else if (props.rounding === "none") rules += ' s1-_rs1-a';
                if (props.rounding === "full") rules += ' s1-_ss1-f';
                else if (props.rounding === "default") rules += ' s1-_ss1-c';
                else if (props.rounding === "none") rules += ' s1-_ss1-a';
                if (props.rounding === "full") rules += ' s1-_ts1-f';
                else if (props.rounding === "default") rules += ' s1-_ts1-c';
                else if (props.rounding === "none") rules += ' s1-_ts1-a';
                return rules;
            })({
                ...renderProps,
                rounding: rounding
            })
    }, ({ isSelected: isSelected })=>/*#__PURE__*/ (0, $1Jad0$react).createElement((0, $1Jad0$react).Fragment, null, swatch, isSelected && /*#__PURE__*/ (0, $1Jad0$react).createElement("div", {
            "aria-hidden": true,
            className: function anonymous(props) {
                let rules = "";
                rules += ' s1-Xs1-a';
                rules += ' s1-__zs1-a';
                rules += ' s1-0s1-a';
                rules += ' s1-2s1-a';
                rules += ' s1-1s1-a';
                rules += ' s1-3s1-a';
                rules += ' s1-_p-1icpv9v';
                rules += ' s1-_us1-b';
                rules += ' s1-_q-17zqamw';
                rules += ' s1-_r-17zqamw';
                rules += ' s1-_s-17zqamw';
                rules += ' s1-_t-17zqamw';
                return rules;
            }()
        })));
}


export {$7e1c53aba5966059$export$b46792416e3d8515 as ColorSwatchPicker};
//# sourceMappingURL=ColorSwatchPicker.js.map
