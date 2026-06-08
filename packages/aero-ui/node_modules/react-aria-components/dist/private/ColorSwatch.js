import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {useColorSwatch as $7Sh0g$useColorSwatch} from "react-aria/useColorSwatch";
import {filterDOMProps as $7Sh0g$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $7Sh0g$mergeProps} from "react-aria/mergeProps";
import $7Sh0g$react, {createContext as $7Sh0g$createContext, forwardRef as $7Sh0g$forwardRef} from "react";






const $2945c4591e267b33$export$83cc445538396800 = /*#__PURE__*/ (0, $7Sh0g$createContext)(null);
const $2945c4591e267b33$export$cae13e90592f246a = /*#__PURE__*/ (0, $7Sh0g$forwardRef)(function ColorSwatch(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $2945c4591e267b33$export$83cc445538396800);
    let { colorSwatchProps: colorSwatchProps, color: color } = (0, $7Sh0g$useColorSwatch)(props);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorSwatch',
        defaultStyle: colorSwatchProps.style,
        values: {
            color: color
        }
    });
    let DOMProps = (0, $7Sh0g$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $7Sh0g$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $7Sh0g$mergeProps)(DOMProps, colorSwatchProps, renderProps),
        slot: props.slot || undefined,
        ref: ref
    });
});


export {$2945c4591e267b33$export$83cc445538396800 as ColorSwatchContext, $2945c4591e267b33$export$cae13e90592f246a as ColorSwatch};
//# sourceMappingURL=ColorSwatch.js.map
