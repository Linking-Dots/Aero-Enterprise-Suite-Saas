import {Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {ColorAreaContext as $e3bcd4910eec2b11$export$ebe63fadcdce34ed} from "./ColorArea.mjs";
import {ColorFieldContext as $baf01eb6a6ce4d91$export$44644b8a16031b5b} from "./ColorField.mjs";
import {ColorSliderContext as $016f94378f03b8fe$export$717b2c0a523a0b53} from "./ColorSlider.mjs";
import {ColorSwatchContext as $eeaff5a2d2421ecc$export$83cc445538396800} from "./ColorSwatch.mjs";
import {ColorSwatchPickerContext as $a36727cf8b43b57f$export$7214f50881fc1eaf} from "./ColorSwatchPicker.mjs";
import {ColorWheelContext as $60f561a24f796e40$export$265015d6dc85bf21} from "./ColorWheel.mjs";
import {useColorPickerState as $fpEtU$useColorPickerState} from "react-stately/useColorPickerState";
import {mergeProps as $fpEtU$mergeProps} from "react-aria/mergeProps";
import $fpEtU$react, {createContext as $fpEtU$createContext} from "react";

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









const $ecdcee4908a8ebb1$export$cfac98503b32f6d6 = /*#__PURE__*/ (0, $fpEtU$createContext)(null);
const $ecdcee4908a8ebb1$export$2c14261be40a385f = /*#__PURE__*/ (0, $fpEtU$createContext)(null);
function $ecdcee4908a8ebb1$export$9feb1bc2e5f1ccb3(props) {
    let ctx = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($ecdcee4908a8ebb1$export$cfac98503b32f6d6, props.slot);
    props = (0, $fpEtU$mergeProps)(ctx, props);
    let state = (0, $fpEtU$useColorPickerState)(props);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            color: state.color
        }
    });
    return /*#__PURE__*/ (0, $fpEtU$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $ecdcee4908a8ebb1$export$2c14261be40a385f,
                state
            ],
            [
                (0, $016f94378f03b8fe$export$717b2c0a523a0b53),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $e3bcd4910eec2b11$export$ebe63fadcdce34ed),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $60f561a24f796e40$export$265015d6dc85bf21),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $baf01eb6a6ce4d91$export$44644b8a16031b5b),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $eeaff5a2d2421ecc$export$83cc445538396800),
                {
                    color: state.color
                }
            ],
            [
                (0, $a36727cf8b43b57f$export$7214f50881fc1eaf),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ]
        ]
    }, renderProps.children);
}


export {$ecdcee4908a8ebb1$export$cfac98503b32f6d6 as ColorPickerContext, $ecdcee4908a8ebb1$export$2c14261be40a385f as ColorPickerStateContext, $ecdcee4908a8ebb1$export$9feb1bc2e5f1ccb3 as ColorPicker};
//# sourceMappingURL=ColorPicker.mjs.map
