import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {mergeProps as $8MsPE$mergeProps} from "react-aria/mergeProps";
import $8MsPE$react, {createContext as $8MsPE$createContext, forwardRef as $8MsPE$forwardRef} from "react";
import {useFocusRing as $8MsPE$useFocusRing} from "react-aria/useFocusRing";
import {useHover as $8MsPE$useHover} from "react-aria/useHover";






const $bd263d78e9bf3c56$export$2dc6166a7e65358c = /*#__PURE__*/ (0, $8MsPE$createContext)({});
let $bd263d78e9bf3c56$var$filterHoverProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $bd263d78e9bf3c56$export$f5c9f3c2c4054eec = /*#__PURE__*/ (0, $8MsPE$forwardRef)(function TextArea(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $bd263d78e9bf3c56$export$2dc6166a7e65358c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $8MsPE$useHover)(props);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $8MsPE$useFocusRing)({
        isTextInput: true,
        autoFocus: props.autoFocus
    });
    let isInvalid = !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
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
    return /*#__PURE__*/ (0, $8MsPE$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).textarea, {
        ...(0, $8MsPE$mergeProps)($bd263d78e9bf3c56$var$filterHoverProps(props), focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-disabled": props.disabled || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-invalid": isInvalid || undefined
    });
});


export {$bd263d78e9bf3c56$export$2dc6166a7e65358c as TextAreaContext, $bd263d78e9bf3c56$export$f5c9f3c2c4054eec as TextArea};
//# sourceMappingURL=TextArea.mjs.map
