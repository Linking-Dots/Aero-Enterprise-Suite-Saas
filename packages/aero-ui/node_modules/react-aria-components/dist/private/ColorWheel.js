import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {InternalColorThumbContext as $80f3336a74d25baa$export$c80c0ea2ca5cb846} from "./ColorThumb.js";
import {useColorWheel as $dDqoU$useColorWheel} from "react-aria/useColorWheel";
import {useColorWheelState as $dDqoU$useColorWheelState} from "react-stately/useColorWheelState";
import {filterDOMProps as $dDqoU$filterDOMProps} from "react-aria/filterDOMProps";
import $dDqoU$react, {createContext as $dDqoU$createContext, forwardRef as $dDqoU$forwardRef, useRef as $dDqoU$useRef, useContext as $dDqoU$useContext} from "react";







const $8ae6a3a7cd656f39$export$265015d6dc85bf21 = /*#__PURE__*/ (0, $dDqoU$createContext)(null);
const $8ae6a3a7cd656f39$export$f5327df9fc840d47 = /*#__PURE__*/ (0, $dDqoU$createContext)(null);
const $8ae6a3a7cd656f39$export$f80663f808113381 = /*#__PURE__*/ (0, $dDqoU$forwardRef)(function ColorWheel(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $8ae6a3a7cd656f39$export$265015d6dc85bf21);
    let state = (0, $dDqoU$useColorWheelState)(props);
    let inputRef = (0, $dDqoU$useRef)(null);
    let { trackProps: trackProps, inputProps: inputProps, thumbProps: thumbProps } = (0, $dDqoU$useColorWheel)(props, state, inputRef);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $dDqoU$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $dDqoU$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $dDqoU$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $8ae6a3a7cd656f39$export$f5327df9fc840d47,
                state
            ],
            [
                $8ae6a3a7cd656f39$export$aec8299548648839,
                trackProps
            ],
            [
                (0, $80f3336a74d25baa$export$c80c0ea2ca5cb846),
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
const $8ae6a3a7cd656f39$export$aec8299548648839 = /*#__PURE__*/ (0, $dDqoU$createContext)(null);
const $8ae6a3a7cd656f39$export$aaae3dd1f909c692 = /*#__PURE__*/ (0, $dDqoU$forwardRef)(function ColorWheelTrack(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $8ae6a3a7cd656f39$export$aec8299548648839);
    let state = (0, $dDqoU$useContext)($8ae6a3a7cd656f39$export$f5327df9fc840d47);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { className: className, style: style, ...rest } = props;
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorWheelTrack',
        values: {
            isDisabled: state.isDisabled,
            state: state
        }
    });
    return /*#__PURE__*/ (0, $dDqoU$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...rest,
        ...renderProps,
        ref: ref,
        "data-disabled": state.isDisabled || undefined
    });
});


export {$8ae6a3a7cd656f39$export$265015d6dc85bf21 as ColorWheelContext, $8ae6a3a7cd656f39$export$f5327df9fc840d47 as ColorWheelStateContext, $8ae6a3a7cd656f39$export$f80663f808113381 as ColorWheel, $8ae6a3a7cd656f39$export$aec8299548648839 as ColorWheelTrackContext, $8ae6a3a7cd656f39$export$aaae3dd1f909c692 as ColorWheelTrack};
//# sourceMappingURL=ColorWheel.js.map
