var $048d76b84370f141$exports = require("./utils.cjs");
var $a5oKV$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $a5oKV$reactariamergeProps = require("react-aria/mergeProps");
var $a5oKV$react = require("react");
var $a5oKV$reactariauseFocusRing = require("react-aria/useFocusRing");
var $a5oKV$reactariauseHover = require("react-aria/useHover");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "InternalColorThumbContext", function () { return $e8b1af84c136cd43$export$c80c0ea2ca5cb846; });
$parcel$export(module.exports, "ColorThumb", function () { return $e8b1af84c136cd43$export$a3cc47cee1c1ccc; });






const $e8b1af84c136cd43$export$c80c0ea2ca5cb846 = /*#__PURE__*/ (0, $a5oKV$react.createContext)(null);
const $e8b1af84c136cd43$export$a3cc47cee1c1ccc = /*#__PURE__*/ (0, $a5oKV$react.forwardRef)(function ColorThumb(props, ref) {
    let { state: state, thumbProps: thumbProps, inputXRef: inputXRef, inputYRef: inputYRef, xInputProps: xInputProps, yInputProps: yInputProps, isDisabled: isDisabled = false } = (0, $a5oKV$react.useContext)($e8b1af84c136cd43$export$c80c0ea2ca5cb846);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $a5oKV$reactariauseFocusRing.useFocusRing)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $a5oKV$reactariauseHover.useHover)(props);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $a5oKV$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($a5oKV$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $a5oKV$reactariamergeProps.mergeProps)(thumbProps, hoverProps, DOMProps),
        ...renderProps,
        ref: ref,
        "data-hovered": isHovered || undefined,
        "data-dragging": state.isDragging || undefined,
        "data-focused": isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-disabled": isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($a5oKV$react))).createElement("input", {
        ref: inputXRef,
        ...xInputProps,
        ...focusProps
    }), yInputProps && /*#__PURE__*/ (0, ($parcel$interopDefault($a5oKV$react))).createElement("input", {
        ref: inputYRef,
        ...yInputProps,
        ...focusProps
    }), renderProps.children);
});


//# sourceMappingURL=ColorThumb.cjs.map
