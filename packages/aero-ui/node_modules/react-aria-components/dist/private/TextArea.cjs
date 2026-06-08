var $048d76b84370f141$exports = require("./utils.cjs");
var $e8sLi$reactariamergeProps = require("react-aria/mergeProps");
var $e8sLi$react = require("react");
var $e8sLi$reactariauseFocusRing = require("react-aria/useFocusRing");
var $e8sLi$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "TextAreaContext", function () { return $ad63e8449e461d5d$export$2dc6166a7e65358c; });
$parcel$export(module.exports, "TextArea", function () { return $ad63e8449e461d5d$export$f5c9f3c2c4054eec; });





const $ad63e8449e461d5d$export$2dc6166a7e65358c = /*#__PURE__*/ (0, $e8sLi$react.createContext)({});
let $ad63e8449e461d5d$var$filterHoverProps = (props)=>{
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { onHoverStart: onHoverStart, onHoverChange: onHoverChange, onHoverEnd: onHoverEnd, ...otherProps } = props;
    return otherProps;
};
const $ad63e8449e461d5d$export$f5c9f3c2c4054eec = /*#__PURE__*/ (0, $e8sLi$react.forwardRef)(function TextArea(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $ad63e8449e461d5d$export$2dc6166a7e65358c);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $e8sLi$reactariauseHover.useHover)(props);
    let { isFocused: isFocused, isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $e8sLi$reactariauseFocusRing.useFocusRing)({
        isTextInput: true,
        autoFocus: props.autoFocus
    });
    let isInvalid = !!props['aria-invalid'] && props['aria-invalid'] !== 'false';
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    return /*#__PURE__*/ (0, ($parcel$interopDefault($e8sLi$react))).createElement((0, $048d76b84370f141$exports.dom).textarea, {
        ...(0, $e8sLi$reactariamergeProps.mergeProps)($ad63e8449e461d5d$var$filterHoverProps(props), focusProps, hoverProps),
        ...renderProps,
        ref: ref,
        "data-focused": isFocused || undefined,
        "data-disabled": props.disabled || undefined,
        "data-hovered": isHovered || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-invalid": isInvalid || undefined
    });
});


//# sourceMappingURL=TextArea.cjs.map
