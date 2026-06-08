import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ColorThumb as $ebb08d0afd4c10ba$export$a3cc47cee1c1ccc} from "./ColorThumb.mjs";
import {dimensionValue as $63d03c54ca5e4b88$export$abc24f5b99744ea6, useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import "../colorarea_vars.css";
import $3IZjM$colorarea_vars_cssmjs from "../colorarea_vars_css.mjs";
import {useFocusableRef as $3c2c983d5210446c$export$96a734597687c040} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useColorArea as $3IZjM$useColorArea} from "react-aria/useColorArea";
import {ColorAreaContext as $3IZjM$ColorAreaContext} from "react-aria-components/ColorArea";
import {mergeProps as $3IZjM$mergeProps} from "react-aria/mergeProps";
import $3IZjM$react, {useRef as $3IZjM$useRef} from "react";
import {useColorAreaState as $3IZjM$useColorAreaState} from "react-stately/useColorAreaState";
import {useContextProps as $3IZjM$useContextProps} from "react-aria-components/slots";
import {useFocusRing as $3IZjM$useFocusRing} from "react-aria/useFocusRing";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2021 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 












const $6600f74a2ea71e97$export$b2103f68a961418e = /*#__PURE__*/ (0, $3IZjM$react).forwardRef(function ColorArea(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let inputXRef = (0, $3IZjM$useRef)(null);
    let inputYRef = (0, $3IZjM$useRef)(null);
    let containerRef = (0, $3c2c983d5210446c$export$96a734597687c040)(ref, inputXRef);
    [props, containerRef] = (0, $3IZjM$useContextProps)(props, containerRef, (0, $3IZjM$ColorAreaContext));
    let { isDisabled: isDisabled } = props;
    let size = props.size && (0, $63d03c54ca5e4b88$export$abc24f5b99744ea6)(props.size);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let state = (0, $3IZjM$useColorAreaState)(props);
    let { colorAreaProps: colorAreaProps, xInputProps: xInputProps, yInputProps: yInputProps, thumbProps: thumbProps } = (0, $3IZjM$useColorArea)({
        ...props,
        inputXRef: inputXRef,
        inputYRef: inputYRef,
        containerRef: containerRef
    }, state);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $3IZjM$useFocusRing)();
    return /*#__PURE__*/ (0, $3IZjM$react).createElement("div", {
        ...colorAreaProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3IZjM$colorarea_vars_cssmjs))), 'spectrum-ColorArea', {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: containerRef,
        style: {
            ...isDisabled ? {} : colorAreaProps.style,
            ...styleProps.style,
            // Workaround around https://github.com/adobe/spectrum-css/issues/1032
            width: size,
            height: size
        }
    }, /*#__PURE__*/ (0, $3IZjM$react).createElement((0, $ebb08d0afd4c10ba$export$a3cc47cee1c1ccc), {
        value: state.getDisplayColor(),
        isFocused: isFocusVisible,
        isDisabled: isDisabled,
        isDragging: state.isDragging,
        containerRef: containerRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3IZjM$colorarea_vars_cssmjs))), 'spectrum-ColorArea-handle'),
        ...thumbProps
    }, /*#__PURE__*/ (0, $3IZjM$react).createElement("div", {
        role: "presentation"
    }, /*#__PURE__*/ (0, $3IZjM$react).createElement("input", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3IZjM$colorarea_vars_cssmjs))), 'spectrum-ColorArea-slider'),
        ...(0, $3IZjM$mergeProps)(xInputProps, focusProps),
        ref: inputXRef
    }), /*#__PURE__*/ (0, $3IZjM$react).createElement("input", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($3IZjM$colorarea_vars_cssmjs))), 'spectrum-ColorArea-slider'),
        ...(0, $3IZjM$mergeProps)(yInputProps, focusProps),
        ref: inputYRef
    }))));
});


export {$6600f74a2ea71e97$export$b2103f68a961418e as ColorArea};
//# sourceMappingURL=ColorArea.mjs.map
