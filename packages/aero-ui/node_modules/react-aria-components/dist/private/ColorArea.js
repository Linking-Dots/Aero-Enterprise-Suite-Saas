import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {InternalColorThumbContext as $80f3336a74d25baa$export$c80c0ea2ca5cb846} from "./ColorThumb.js";
import {useColorArea as $a5Fl3$useColorArea} from "react-aria/useColorArea";
import {useColorAreaState as $a5Fl3$useColorAreaState} from "react-stately/useColorAreaState";
import {filterDOMProps as $a5Fl3$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $a5Fl3$mergeProps} from "react-aria/mergeProps";
import $a5Fl3$react, {createContext as $a5Fl3$createContext, forwardRef as $a5Fl3$forwardRef, useRef as $a5Fl3$useRef} from "react";








const $a78e35c6fd3df8be$export$ebe63fadcdce34ed = /*#__PURE__*/ (0, $a5Fl3$createContext)(null);
const $a78e35c6fd3df8be$export$6b32221de49982e = /*#__PURE__*/ (0, $a5Fl3$createContext)(null);
const $a78e35c6fd3df8be$export$b2103f68a961418e = /*#__PURE__*/ (0, $a5Fl3$forwardRef)(function ColorArea(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $a78e35c6fd3df8be$export$ebe63fadcdce34ed);
    let inputXRef = (0, $a5Fl3$useRef)(null);
    let inputYRef = (0, $a5Fl3$useRef)(null);
    let state = (0, $a5Fl3$useColorAreaState)(props);
    let { colorAreaProps: colorAreaProps, xInputProps: xInputProps, yInputProps: yInputProps, thumbProps: thumbProps } = (0, $a5Fl3$useColorArea)({
        ...props,
        inputXRef: inputXRef,
        inputYRef: inputYRef,
        containerRef: ref
    }, state);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorArea',
        defaultStyle: colorAreaProps.style,
        values: {
            state: state,
            isDisabled: props.isDisabled || false
        }
    });
    let DOMProps = (0, $a5Fl3$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $a5Fl3$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ref: ref,
        ...(0, $a5Fl3$mergeProps)(DOMProps, colorAreaProps, renderProps),
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $a5Fl3$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $a78e35c6fd3df8be$export$6b32221de49982e,
                state
            ],
            [
                (0, $80f3336a74d25baa$export$c80c0ea2ca5cb846),
                {
                    state: state,
                    thumbProps: thumbProps,
                    inputXRef: inputXRef,
                    xInputProps: xInputProps,
                    inputYRef: inputYRef,
                    yInputProps: yInputProps,
                    isDisabled: props.isDisabled
                }
            ]
        ]
    }, renderProps.children));
});


export {$a78e35c6fd3df8be$export$ebe63fadcdce34ed as ColorAreaContext, $a78e35c6fd3df8be$export$6b32221de49982e as ColorAreaStateContext, $a78e35c6fd3df8be$export$b2103f68a961418e as ColorArea};
//# sourceMappingURL=ColorArea.js.map
