var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $9b9b2ae635bd46b3$exports = require("./ColorThumb.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
require("../colorarea_vars.css");
var $1b7d5e46b73517b5$exports = require("../colorarea_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $j0sm4$reactariauseColorArea = require("react-aria/useColorArea");
var $j0sm4$reactariacomponentsColorArea = require("react-aria-components/ColorArea");
var $j0sm4$reactariamergeProps = require("react-aria/mergeProps");
var $j0sm4$react = require("react");
var $j0sm4$reactstatelyuseColorAreaState = require("react-stately/useColorAreaState");
var $j0sm4$reactariacomponentsslots = require("react-aria-components/slots");
var $j0sm4$reactariauseFocusRing = require("react-aria/useFocusRing");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorArea", function () { return $74f6990c4ec74329$export$b2103f68a961418e; });
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












const $74f6990c4ec74329$export$b2103f68a961418e = /*#__PURE__*/ (0, ($parcel$interopDefault($j0sm4$react))).forwardRef(function ColorArea(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let inputXRef = (0, $j0sm4$react.useRef)(null);
    let inputYRef = (0, $j0sm4$react.useRef)(null);
    let containerRef = (0, $65aea7b37663976b$exports.useFocusableRef)(ref, inputXRef);
    [props, containerRef] = (0, $j0sm4$reactariacomponentsslots.useContextProps)(props, containerRef, (0, $j0sm4$reactariacomponentsColorArea.ColorAreaContext));
    let { isDisabled: isDisabled } = props;
    let size = props.size && (0, $b8f90d51c4908137$exports.dimensionValue)(props.size);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let state = (0, $j0sm4$reactstatelyuseColorAreaState.useColorAreaState)(props);
    let { colorAreaProps: colorAreaProps, xInputProps: xInputProps, yInputProps: yInputProps, thumbProps: thumbProps } = (0, $j0sm4$reactariauseColorArea.useColorArea)({
        ...props,
        inputXRef: inputXRef,
        inputYRef: inputYRef,
        containerRef: containerRef
    }, state);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $j0sm4$reactariauseFocusRing.useFocusRing)();
    return /*#__PURE__*/ (0, ($parcel$interopDefault($j0sm4$react))).createElement("div", {
        ...colorAreaProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1b7d5e46b73517b5$exports))), 'spectrum-ColorArea', {
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($j0sm4$react))).createElement((0, $9b9b2ae635bd46b3$exports.ColorThumb), {
        value: state.getDisplayColor(),
        isFocused: isFocusVisible,
        isDisabled: isDisabled,
        isDragging: state.isDragging,
        containerRef: containerRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1b7d5e46b73517b5$exports))), 'spectrum-ColorArea-handle'),
        ...thumbProps
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($j0sm4$react))).createElement("div", {
        role: "presentation"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($j0sm4$react))).createElement("input", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1b7d5e46b73517b5$exports))), 'spectrum-ColorArea-slider'),
        ...(0, $j0sm4$reactariamergeProps.mergeProps)(xInputProps, focusProps),
        ref: inputXRef
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($j0sm4$react))).createElement("input", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1b7d5e46b73517b5$exports))), 'spectrum-ColorArea-slider'),
        ...(0, $j0sm4$reactariamergeProps.mergeProps)(yInputProps, focusProps),
        ref: inputYRef
    }))));
});


//# sourceMappingURL=ColorArea.cjs.map
