import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {useColorSwatch as $4OWOD$useColorSwatch} from "react-aria/useColorSwatch";
import {filterDOMProps as $4OWOD$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $4OWOD$mergeProps} from "react-aria/mergeProps";
import $4OWOD$react, {createContext as $4OWOD$createContext, forwardRef as $4OWOD$forwardRef} from "react";






const $eeaff5a2d2421ecc$export$83cc445538396800 = /*#__PURE__*/ (0, $4OWOD$createContext)(null);
const $eeaff5a2d2421ecc$export$cae13e90592f246a = /*#__PURE__*/ (0, $4OWOD$forwardRef)(function ColorSwatch(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $eeaff5a2d2421ecc$export$83cc445538396800);
    let { colorSwatchProps: colorSwatchProps, color: color } = (0, $4OWOD$useColorSwatch)(props);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorSwatch',
        defaultStyle: colorSwatchProps.style,
        values: {
            color: color
        }
    });
    let DOMProps = (0, $4OWOD$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $4OWOD$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $4OWOD$mergeProps)(DOMProps, colorSwatchProps, renderProps),
        slot: props.slot || undefined,
        ref: ref
    });
});


export {$eeaff5a2d2421ecc$export$83cc445538396800 as ColorSwatchContext, $eeaff5a2d2421ecc$export$cae13e90592f246a as ColorSwatch};
//# sourceMappingURL=ColorSwatch.mjs.map
