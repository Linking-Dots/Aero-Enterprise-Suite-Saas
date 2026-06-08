import {Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {ColorAreaContext as $a78e35c6fd3df8be$export$ebe63fadcdce34ed} from "./ColorArea.js";
import {ColorFieldContext as $6ca9ff78887ad6c3$export$44644b8a16031b5b} from "./ColorField.js";
import {ColorSliderContext as $957c6e562facdeae$export$717b2c0a523a0b53} from "./ColorSlider.js";
import {ColorSwatchContext as $2945c4591e267b33$export$83cc445538396800} from "./ColorSwatch.js";
import {ColorSwatchPickerContext as $8a52f212098a563d$export$7214f50881fc1eaf} from "./ColorSwatchPicker.js";
import {ColorWheelContext as $8ae6a3a7cd656f39$export$265015d6dc85bf21} from "./ColorWheel.js";
import {useColorPickerState as $hIk0J$useColorPickerState} from "react-stately/useColorPickerState";
import {mergeProps as $hIk0J$mergeProps} from "react-aria/mergeProps";
import $hIk0J$react, {createContext as $hIk0J$createContext} from "react";

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









const $8be8ae0adb439a7a$export$cfac98503b32f6d6 = /*#__PURE__*/ (0, $hIk0J$createContext)(null);
const $8be8ae0adb439a7a$export$2c14261be40a385f = /*#__PURE__*/ (0, $hIk0J$createContext)(null);
function $8be8ae0adb439a7a$export$9feb1bc2e5f1ccb3(props) {
    let ctx = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)($8be8ae0adb439a7a$export$cfac98503b32f6d6, props.slot);
    props = (0, $hIk0J$mergeProps)(ctx, props);
    let state = (0, $hIk0J$useColorPickerState)(props);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            color: state.color
        }
    });
    return /*#__PURE__*/ (0, $hIk0J$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $8be8ae0adb439a7a$export$2c14261be40a385f,
                state
            ],
            [
                (0, $957c6e562facdeae$export$717b2c0a523a0b53),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $a78e35c6fd3df8be$export$ebe63fadcdce34ed),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $8ae6a3a7cd656f39$export$265015d6dc85bf21),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $6ca9ff78887ad6c3$export$44644b8a16031b5b),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ],
            [
                (0, $2945c4591e267b33$export$83cc445538396800),
                {
                    color: state.color
                }
            ],
            [
                (0, $8a52f212098a563d$export$7214f50881fc1eaf),
                {
                    value: state.color,
                    onChange: state.setColor
                }
            ]
        ]
    }, renderProps.children);
}


export {$8be8ae0adb439a7a$export$cfac98503b32f6d6 as ColorPickerContext, $8be8ae0adb439a7a$export$2c14261be40a385f as ColorPickerStateContext, $8be8ae0adb439a7a$export$9feb1bc2e5f1ccb3 as ColorPicker};
//# sourceMappingURL=ColorPicker.js.map
