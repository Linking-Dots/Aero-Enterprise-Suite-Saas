import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {InternalColorThumbContext as $ceafedee624ffe11$export$c80c0ea2ca5cb846} from "./ColorThumb.mjs";
import {useColorWheel as $pqA4I$useColorWheel} from "react-aria/useColorWheel";
import {useColorWheelState as $pqA4I$useColorWheelState} from "react-stately/useColorWheelState";
import {filterDOMProps as $pqA4I$filterDOMProps} from "react-aria/filterDOMProps";
import $pqA4I$react, {createContext as $pqA4I$createContext, forwardRef as $pqA4I$forwardRef, useRef as $pqA4I$useRef, useContext as $pqA4I$useContext} from "react";







const $60f561a24f796e40$export$265015d6dc85bf21 = /*#__PURE__*/ (0, $pqA4I$createContext)(null);
const $60f561a24f796e40$export$f5327df9fc840d47 = /*#__PURE__*/ (0, $pqA4I$createContext)(null);
const $60f561a24f796e40$export$f80663f808113381 = /*#__PURE__*/ (0, $pqA4I$forwardRef)(function ColorWheel(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $60f561a24f796e40$export$265015d6dc85bf21);
    let state = (0, $pqA4I$useColorWheelState)(props);
    let inputRef = (0, $pqA4I$useRef)(null);
    let { trackProps: trackProps, inputProps: inputProps, thumbProps: thumbProps } = (0, $pqA4I$useColorWheel)(props, state, inputRef);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            state: state,
            isDisabled: props.isDisabled || false
        },
        defaultClassName: 'react-aria-ColorWheel',
        defaultStyle: {
            position: 'relative'
        }
    });
    let DOMProps = (0, $pqA4I$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $pqA4I$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $pqA4I$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $60f561a24f796e40$export$f5327df9fc840d47,
                state
            ],
            [
                $60f561a24f796e40$export$aec8299548648839,
                trackProps
            ],
            [
                (0, $ceafedee624ffe11$export$c80c0ea2ca5cb846),
                {
                    state: state,
                    thumbProps: thumbProps,
                    inputXRef: inputRef,
                    xInputProps: inputProps,
                    isDisabled: props.isDisabled
                }
            ]
        ]
    }, renderProps.children));
});
const $60f561a24f796e40$export$aec8299548648839 = /*#__PURE__*/ (0, $pqA4I$createContext)(null);
const $60f561a24f796e40$export$aaae3dd1f909c692 = /*#__PURE__*/ (0, $pqA4I$forwardRef)(function ColorWheelTrack(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $60f561a24f796e40$export$aec8299548648839);
    let state = (0, $pqA4I$useContext)($60f561a24f796e40$export$f5327df9fc840d47);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { className: className, style: style, ...rest } = props;
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorWheelTrack',
        values: {
            isDisabled: state.isDisabled,
            state: state
        }
    });
    return /*#__PURE__*/ (0, $pqA4I$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...rest,
        ...renderProps,
        ref: ref,
        "data-disabled": state.isDisabled || undefined
    });
});


export {$60f561a24f796e40$export$265015d6dc85bf21 as ColorWheelContext, $60f561a24f796e40$export$f5327df9fc840d47 as ColorWheelStateContext, $60f561a24f796e40$export$f80663f808113381 as ColorWheel, $60f561a24f796e40$export$aec8299548648839 as ColorWheelTrackContext, $60f561a24f796e40$export$aaae3dd1f909c692 as ColorWheelTrack};
//# sourceMappingURL=ColorWheel.mjs.map
