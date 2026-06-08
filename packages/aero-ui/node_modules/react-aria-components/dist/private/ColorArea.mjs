import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {InternalColorThumbContext as $ceafedee624ffe11$export$c80c0ea2ca5cb846} from "./ColorThumb.mjs";
import {useColorArea as $2zlb2$useColorArea} from "react-aria/useColorArea";
import {useColorAreaState as $2zlb2$useColorAreaState} from "react-stately/useColorAreaState";
import {filterDOMProps as $2zlb2$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $2zlb2$mergeProps} from "react-aria/mergeProps";
import $2zlb2$react, {createContext as $2zlb2$createContext, forwardRef as $2zlb2$forwardRef, useRef as $2zlb2$useRef} from "react";








const $e3bcd4910eec2b11$export$ebe63fadcdce34ed = /*#__PURE__*/ (0, $2zlb2$createContext)(null);
const $e3bcd4910eec2b11$export$6b32221de49982e = /*#__PURE__*/ (0, $2zlb2$createContext)(null);
const $e3bcd4910eec2b11$export$b2103f68a961418e = /*#__PURE__*/ (0, $2zlb2$forwardRef)(function ColorArea(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $e3bcd4910eec2b11$export$ebe63fadcdce34ed);
    let inputXRef = (0, $2zlb2$useRef)(null);
    let inputYRef = (0, $2zlb2$useRef)(null);
    let state = (0, $2zlb2$useColorAreaState)(props);
    let { colorAreaProps: colorAreaProps, xInputProps: xInputProps, yInputProps: yInputProps, thumbProps: thumbProps } = (0, $2zlb2$useColorArea)({
        ...props,
        inputXRef: inputXRef,
        inputYRef: inputYRef,
        containerRef: ref
    }, state);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorArea',
        defaultStyle: colorAreaProps.style,
        values: {
            state: state,
            isDisabled: props.isDisabled || false
        }
    });
    let DOMProps = (0, $2zlb2$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $2zlb2$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ref: ref,
        ...(0, $2zlb2$mergeProps)(DOMProps, colorAreaProps, renderProps),
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $2zlb2$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $e3bcd4910eec2b11$export$6b32221de49982e,
                state
            ],
            [
                (0, $ceafedee624ffe11$export$c80c0ea2ca5cb846),
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


export {$e3bcd4910eec2b11$export$ebe63fadcdce34ed as ColorAreaContext, $e3bcd4910eec2b11$export$6b32221de49982e as ColorAreaStateContext, $e3bcd4910eec2b11$export$b2103f68a961418e as ColorArea};
//# sourceMappingURL=ColorArea.mjs.map
