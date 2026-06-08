import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ColorThumb as $7f568464139e11ee$export$a3cc47cee1c1ccc} from "./ColorThumb.js";
import {dimensionValue as $120fbea2d95e11ed$export$abc24f5b99744ea6, useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import "../colorarea_vars.css";
import $9Atgm$colorarea_vars_cssmjs from "../colorarea_vars_css.mjs";
import {useFocusableRef as $c234463e9ef56637$export$96a734597687c040} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useColorArea as $9Atgm$useColorArea} from "react-aria/useColorArea";
import {ColorAreaContext as $9Atgm$ColorAreaContext} from "react-aria-components/ColorArea";
import {mergeProps as $9Atgm$mergeProps} from "react-aria/mergeProps";
import $9Atgm$react, {useRef as $9Atgm$useRef} from "react";
import {useColorAreaState as $9Atgm$useColorAreaState} from "react-stately/useColorAreaState";
import {useContextProps as $9Atgm$useContextProps} from "react-aria-components/slots";
import {useFocusRing as $9Atgm$useFocusRing} from "react-aria/useFocusRing";


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












const $d6409d59c6d41934$export$b2103f68a961418e = /*#__PURE__*/ (0, $9Atgm$react).forwardRef(function ColorArea(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let inputXRef = (0, $9Atgm$useRef)(null);
    let inputYRef = (0, $9Atgm$useRef)(null);
    let containerRef = (0, $c234463e9ef56637$export$96a734597687c040)(ref, inputXRef);
    [props, containerRef] = (0, $9Atgm$useContextProps)(props, containerRef, (0, $9Atgm$ColorAreaContext));
    let { isDisabled: isDisabled } = props;
    let size = props.size && (0, $120fbea2d95e11ed$export$abc24f5b99744ea6)(props.size);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let state = (0, $9Atgm$useColorAreaState)(props);
    let { colorAreaProps: colorAreaProps, xInputProps: xInputProps, yInputProps: yInputProps, thumbProps: thumbProps } = (0, $9Atgm$useColorArea)({
        ...props,
        inputXRef: inputXRef,
        inputYRef: inputYRef,
        containerRef: containerRef
    }, state);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $9Atgm$useFocusRing)();
    return /*#__PURE__*/ (0, $9Atgm$react).createElement("div", {
        ...colorAreaProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9Atgm$colorarea_vars_cssmjs))), 'spectrum-ColorArea', {
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
    }, /*#__PURE__*/ (0, $9Atgm$react).createElement((0, $7f568464139e11ee$export$a3cc47cee1c1ccc), {
        value: state.getDisplayColor(),
        isFocused: isFocusVisible,
        isDisabled: isDisabled,
        isDragging: state.isDragging,
        containerRef: containerRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9Atgm$colorarea_vars_cssmjs))), 'spectrum-ColorArea-handle'),
        ...thumbProps
    }, /*#__PURE__*/ (0, $9Atgm$react).createElement("div", {
        role: "presentation"
    }, /*#__PURE__*/ (0, $9Atgm$react).createElement("input", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9Atgm$colorarea_vars_cssmjs))), 'spectrum-ColorArea-slider'),
        ...(0, $9Atgm$mergeProps)(xInputProps, focusProps),
        ref: inputXRef
    }), /*#__PURE__*/ (0, $9Atgm$react).createElement("input", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9Atgm$colorarea_vars_cssmjs))), 'spectrum-ColorArea-slider'),
        ...(0, $9Atgm$mergeProps)(yInputProps, focusProps),
        ref: inputYRef
    }))));
});


export {$d6409d59c6d41934$export$b2103f68a961418e as ColorArea};
//# sourceMappingURL=ColorArea.js.map
