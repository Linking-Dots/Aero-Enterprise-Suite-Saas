var $048d76b84370f141$exports = require("./utils.cjs");
var $e8b1af84c136cd43$exports = require("./ColorThumb.cjs");
var $j57d9$reactariauseColorArea = require("react-aria/useColorArea");
var $j57d9$reactstatelyuseColorAreaState = require("react-stately/useColorAreaState");
var $j57d9$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $j57d9$reactariamergeProps = require("react-aria/mergeProps");
var $j57d9$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorAreaContext", function () { return $1d24456273dee3e7$export$ebe63fadcdce34ed; });
$parcel$export(module.exports, "ColorAreaStateContext", function () { return $1d24456273dee3e7$export$6b32221de49982e; });
$parcel$export(module.exports, "ColorArea", function () { return $1d24456273dee3e7$export$b2103f68a961418e; });







const $1d24456273dee3e7$export$ebe63fadcdce34ed = /*#__PURE__*/ (0, $j57d9$react.createContext)(null);
const $1d24456273dee3e7$export$6b32221de49982e = /*#__PURE__*/ (0, $j57d9$react.createContext)(null);
const $1d24456273dee3e7$export$b2103f68a961418e = /*#__PURE__*/ (0, $j57d9$react.forwardRef)(function ColorArea(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $1d24456273dee3e7$export$ebe63fadcdce34ed);
    let inputXRef = (0, $j57d9$react.useRef)(null);
    let inputYRef = (0, $j57d9$react.useRef)(null);
    let state = (0, $j57d9$reactstatelyuseColorAreaState.useColorAreaState)(props);
    let { colorAreaProps: colorAreaProps, xInputProps: xInputProps, yInputProps: yInputProps, thumbProps: thumbProps } = (0, $j57d9$reactariauseColorArea.useColorArea)({
        ...props,
        inputXRef: inputXRef,
        inputYRef: inputYRef,
        containerRef: ref
    }, state);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-ColorArea',
        defaultStyle: colorAreaProps.style,
        values: {
            state: state,
            isDisabled: props.isDisabled || false
        }
    });
    let DOMProps = (0, $j57d9$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($j57d9$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ref: ref,
        ...(0, $j57d9$reactariamergeProps.mergeProps)(DOMProps, colorAreaProps, renderProps),
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($j57d9$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $1d24456273dee3e7$export$6b32221de49982e,
                state
            ],
            [
                (0, $e8b1af84c136cd43$exports.InternalColorThumbContext),
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


//# sourceMappingURL=ColorArea.cjs.map
