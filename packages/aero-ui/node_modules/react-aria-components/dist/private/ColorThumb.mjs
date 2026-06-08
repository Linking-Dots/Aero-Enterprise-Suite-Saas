import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {filterDOMProps as $fqlfY$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $fqlfY$mergeProps} from "react-aria/mergeProps";
import $fqlfY$react, {createContext as $fqlfY$createContext, forwardRef as $fqlfY$forwardRef, useContext as $fqlfY$useContext} from "react";
import {useFocusRing as $fqlfY$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $fqlfY$useHover} from "react-aria/useHover";







const $ceafedee624ffe11$export$c80c0ea2ca5cb846 = /*#__PURE__*/ (0, $fqlfY$createContext)(null);
const $ceafedee624ffe11$export$a3cc47cee1c1ccc = /*#__PURE__*/ (0, $fqlfY$forwardRef)(function ColorThumb(props, ref) {
    let { state: state, thumbProps: thumbProps, inputXRef: inputXRef, inputYRef: inputYRef, xInputProps: xInputProps, yInputProps: yInputProps, isDisabled: isDisabled = false } = (0, $fqlfY$useContext)($ceafedee624ffe11$export$c80c0ea2ca5cb846);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $fqlfY$useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $fqlfY$useHover)(props);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    let DOMProps = (0, $fqlfY$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $fqlfY$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $fqlfY$mergeProps)(thumbProps, hoverProps, DOMProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-dragging": state.isDragging || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined
    }, /*#__PURE__*/ (0, $fqlfY$react).createElement("input", {
        ref: inputXRef,
        ...xInputProps,
        ...focusProps
    }), yInputProps && /*#__PURE__*/ (0, $fqlfY$react).createElement("input", {
        ref: inputYRef,
        ...yInputProps,
        ...focusProps
    }), renderProps.children);
});


export {$ceafedee624ffe11$export$c80c0ea2ca5cb846 as InternalColorThumbContext, $ceafedee624ffe11$export$a3cc47cee1c1ccc as ColorThumb};
//# sourceMappingURL=ColorThumb.mjs.map
