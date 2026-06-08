require("./ColorSwatchPicker.css");
var $a6faa7a1d4cc9146$exports = require("./ColorSwatch.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $8pgro$reactariacomponentsColorSwatchPicker = require("react-aria-components/ColorSwatchPicker");
var $8pgro$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorSwatchPicker", function () { return $5b41c2c121bbfe88$export$b46792416e3d8515; });
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




const $5b41c2c121bbfe88$export$b46792416e3d8515 = /*#__PURE__*/ (0, $8pgro$react.forwardRef)(function ColorSwatchPicker(props, ref) {
    let { density: density = 'regular', size: size = 'M', rounding: rounding = 'none', ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8pgro$react))).createElement((0, $8pgro$reactariacomponentsColorSwatchPicker.ColorSwatchPicker), {
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8pgro$react))).createElement((0, $a6faa7a1d4cc9146$exports.SpectrumColorSwatchContext).Provider, {
        value: {
            useWrapper: $5b41c2c121bbfe88$var$useWrapper,
            size: size,
            rounding: rounding
        }
    }, props.children));
});
function $5b41c2c121bbfe88$var$useWrapper(swatch, color, rounding) {
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8pgro$react))).createElement((0, $8pgro$reactariacomponentsColorSwatchPicker.ColorSwatchPickerItem), {
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
    }, ({ isSelected: isSelected })=>/*#__PURE__*/ (0, ($parcel$interopDefault($8pgro$react))).createElement((0, ($parcel$interopDefault($8pgro$react))).Fragment, null, swatch, isSelected && /*#__PURE__*/ (0, ($parcel$interopDefault($8pgro$react))).createElement("div", {
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


//# sourceMappingURL=ColorSwatchPicker.cjs.map
