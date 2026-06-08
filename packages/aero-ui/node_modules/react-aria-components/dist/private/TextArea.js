import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3} from "./utils.js";
import {mergeProps as $6HxdN$mergeProps} from "react-aria/mergeProps";
import $6HxdN$react, {createContext as $6HxdN$createContext, forwardRef as $6HxdN$forwardRef} from "react";
import {useFocusRing as $6HxdN$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $6HxdN$useHover} from "react-aria/useHover";






const $e1a0b7a67b6be0bd$export$2dc6166a7e65358c = /*#__PURE__*/ (0, $6HxdN$createContext)({});
let $e1a0b7a67b6be0bd$var$filterHoverProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $e1a0b7a67b6be0bd$export$f5c9f3c2c4054eec = /*#__PURE__*/ (0, $6HxdN$forwardRef)(function TextArea(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $e1a0b7a67b6be0bd$export$2dc6166a7e65358c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $6HxdN$useHover)(props);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $6HxdN$useFocusRing)({
        isTextInput: true,
        autoFocus: props.autoFocus
    });
    let isInvalid = !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            isHovered: isHovered,
            isFocused: isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.disabled || false,
            isInvalid: isInvalid
        },
        defaultClassName: 'react-aria-TextArea'
    });
    return /*#__PURE__*/ (0, $6HxdN$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).textarea, {
        ...(0, $6HxdN$mergeProps)($e1a0b7a67b6be0bd$var$filterHoverProps(props), focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-disabled": props.disabled || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-invalid": isInvalid || undefined
    });
});


export {$e1a0b7a67b6be0bd$export$2dc6166a7e65358c as TextAreaContext, $e1a0b7a67b6be0bd$export$f5c9f3c2c4054eec as TextArea};
//# sourceMappingURL=TextArea.js.map
