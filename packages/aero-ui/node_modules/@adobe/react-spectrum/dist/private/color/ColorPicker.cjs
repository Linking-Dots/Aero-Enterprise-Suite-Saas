require("./ColorPicker.css");
var $a6faa7a1d4cc9146$exports = require("./ColorSwatch.cjs");
var $685ef7ad6d6d547f$exports = require("../view/Content.cjs");
var $db50fa4488be370e$exports = require("../dialog/Dialog.cjs");
var $d4a85248c617d550$exports = require("../dialog/DialogTrigger.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $4QtUL$reactariacomponentsColorPicker = require("react-aria-components/ColorPicker");
var $4QtUL$reactariacomponentsButton = require("react-aria-components/Button");
var $4QtUL$react = require("react");
var $4QtUL$reactariauseId = require("react-aria/useId");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorPicker", function () { return $ad919327f448f747$export$9feb1bc2e5f1ccb3; });
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








const $ad919327f448f747$export$9feb1bc2e5f1ccb3 = /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).forwardRef(function ColorPicker(props, ref) {
    let swatchRef = (0, $4QtUL$react.useRef)(null);
    let domRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref);
    let labelId = (0, $4QtUL$reactariauseId.useId)();
    return /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, $4QtUL$reactariacomponentsColorPicker.ColorPicker), props, /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, $d4a85248c617d550$exports.DialogTrigger), {
        type: "popover",
        mobileType: "tray",
        targetRef: (0, $65aea7b37663976b$exports.unwrapDOMRef)(swatchRef)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, $4QtUL$reactariacomponentsButton.Button), {
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
    }, ({ isFocusVisible: isFocusVisible })=>/*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, ($parcel$interopDefault($4QtUL$react))).Fragment, null, /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement("div", {
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
        }, /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, $a6faa7a1d4cc9146$exports.ColorSwatch), {
            ref: swatchRef,
            color: props.value,
            size: props.size,
            rounding: props.rounding,
            "aria-label": props['aria-label'],
            "aria-labelledby": props['aria-labelledby'],
            "aria-describedby": props['aria-describedby'],
            "aria-details": props['aria-details']
        })), props.label && /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement("span", {
            id: labelId
        }, props.label))), /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, $db50fa4488be370e$exports.Dialog), {
        "aria-labelledby": props.label ? labelId : props['aria-labelledby'],
        "aria-label": props['aria-label'],
        UNSAFE_style: {
            width: 'fit-content',
            minWidth: 0,
            margin: '0 auto' // Center within tray.
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4QtUL$react))).createElement((0, $685ef7ad6d6d547f$exports.Content), {
        UNSAFE_style: {
            position: 'relative',
            margin: 'calc(var(--spectrum-dialog-padding) * -1)',
            padding: 'var(--spectrum-global-dimension-size-200)'
        }
    }, props.children))));
});


//# sourceMappingURL=ColorPicker.cjs.map
