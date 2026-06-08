import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {filterDOMProps as $jR9rk$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $jR9rk$mergeProps} from "react-aria/mergeProps";
import $jR9rk$react, {createContext as $jR9rk$createContext, forwardRef as $jR9rk$forwardRef, useContext as $jR9rk$useContext} from "react";
import {useFocusRing as $jR9rk$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $jR9rk$useHover} from "react-aria/useHover";







const $80f3336a74d25baa$export$c80c0ea2ca5cb846 = /*#__PURE__*/ (0, $jR9rk$createContext)(null);
const $80f3336a74d25baa$export$a3cc47cee1c1ccc = /*#__PURE__*/ (0, $jR9rk$forwardRef)(function ColorThumb(props, ref) {
    let { state: state, thumbProps: thumbProps, inputXRef: inputXRef, inputYRef: inputYRef, xInputProps: xInputProps, yInputProps: yInputProps, isDisabled: isDisabled = false } = (0, $jR9rk$useContext)($80f3336a74d25baa$export$c80c0ea2ca5cb846);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $jR9rk$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $jR9rk$useHover)(props);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultClassName: 'react-aria-ColorThumb',
        defaultStyle: {
            ...thumbProps.style,
            backgroundColor: state.getDisplayColor().toString()
        },
        values: {
            color: state.getDisplayColor(),
            isHovered: isHovered,
            isDragging: state.isDragging,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: isDisabled
        }
    });
    let DOMProps = (0, $jR9rk$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $jR9rk$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $jR9rk$mergeProps)(thumbProps, hoverProps, DOMProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-dragging": state.isDragging || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined
    }, /*#__PURE__*/ (0, $jR9rk$react).createElement("input", {
        ref: inputXRef,
        ...xInputProps,
        ...focusProps
    }), yInputProps && /*#__PURE__*/ (0, $jR9rk$react).createElement("input", {
        ref: inputYRef,
        ...yInputProps,
        ...focusProps
    }), renderProps.children);
});


export {$80f3336a74d25baa$export$c80c0ea2ca5cb846 as InternalColorThumbContext, $80f3336a74d25baa$export$a3cc47cee1c1ccc as ColorThumb};
//# sourceMappingURL=ColorThumb.js.map
