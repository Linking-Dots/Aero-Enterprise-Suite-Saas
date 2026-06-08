var $048d76b84370f141$exports = require("./utils.cjs");
var $1d24456273dee3e7$exports = require("./ColorArea.cjs");
var $84f5a74f9beac008$exports = require("./ColorField.cjs");
var $2c508fe14bf948ee$exports = require("./ColorSlider.cjs");
var $144cc1383f65bbfe$exports = require("./ColorSwatch.cjs");
var $b3b4abadd57cf07d$exports = require("./ColorSwatchPicker.cjs");
var $9ce2d9a18460c1c0$exports = require("./ColorWheel.cjs");
var $kBdtv$reactstatelyuseColorPickerState = require("react-stately/useColorPickerState");
var $kBdtv$reactariamergeProps = require("react-aria/mergeProps");
var $kBdtv$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorPickerContext", function () { return $bfc438ca0383e8c6$export$cfac98503b32f6d6; });
$parcel$export(module.exports, "ColorPickerStateContext", function () { return $bfc438ca0383e8c6$export$2c14261be40a385f; });
$parcel$export(module.exports, "ColorPicker", function () { return $bfc438ca0383e8c6$export$9feb1bc2e5f1ccb3; });
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









const $bfc438ca0383e8c6$export$cfac98503b32f6d6 = /*#__PURE__*/ (0, $kBdtv$react.createContext)(null);
const $bfc438ca0383e8c6$export$2c14261be40a385f = /*#__PURE__*/ (0, $kBdtv$react.createContext)(null);
function $bfc438ca0383e8c6$export$9feb1bc2e5f1ccb3(props) {
    let ctx = (0, $048d76b84370f141$exports.useSlottedContext)($bfc438ca0383e8c6$export$cfac98503b32f6d6, props.slot);
    props = (0, $kBdtv$reactariamergeProps.mergeProps)(ctx, props);
    let state = (0, $kBdtv$reactstatelyuseColorPickerState.useColorPickerState)(props);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            color: state.color
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($kBdtv$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $bfc438ca0383e8c6$export$2c14261be40a385f,
                state
            ],
            [
                (0, $2c508fe14bf948ee$exports.ColorSliderContext),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $1d24456273dee3e7$exports.ColorAreaContext),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $9ce2d9a18460c1c0$exports.ColorWheelContext),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $84f5a74f9beac008$exports.ColorFieldContext),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $144cc1383f65bbfe$exports.ColorSwatchContext),
                {
                    color: state.color
                }
            ],
            [
                (0, $b3b4abadd57cf07d$exports.ColorSwatchPickerContext),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ]
        ]
    }, renderProps.children);
}


//# sourceMappingURL=ColorPicker.cjs.map
