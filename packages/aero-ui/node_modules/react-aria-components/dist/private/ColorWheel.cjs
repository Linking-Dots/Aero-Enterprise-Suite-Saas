var $048d76b84370f141$exports = require("./utils.cjs");
var $e8b1af84c136cd43$exports = require("./ColorThumb.cjs");
var $iIRsw$reactariauseColorWheel = require("react-aria/useColorWheel");
var $iIRsw$reactstatelyuseColorWheelState = require("react-stately/useColorWheelState");
var $iIRsw$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $iIRsw$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorWheelContext", function () { return $9ce2d9a18460c1c0$export$265015d6dc85bf21; });
$parcel$export(module.exports, "ColorWheelStateContext", function () { return $9ce2d9a18460c1c0$export$f5327df9fc840d47; });
$parcel$export(module.exports, "ColorWheel", function () { return $9ce2d9a18460c1c0$export$f80663f808113381; });
$parcel$export(module.exports, "ColorWheelTrackContext", function () { return $9ce2d9a18460c1c0$export$aec8299548648839; });
$parcel$export(module.exports, "ColorWheelTrack", function () { return $9ce2d9a18460c1c0$export$aaae3dd1f909c692; });






const $9ce2d9a18460c1c0$export$265015d6dc85bf21 = /*#__PURE__*/ (0, $iIRsw$react.createContext)(null);
const $9ce2d9a18460c1c0$export$f5327df9fc840d47 = /*#__PURE__*/ (0, $iIRsw$react.createContext)(null);
const $9ce2d9a18460c1c0$export$f80663f808113381 = /*#__PURE__*/ (0, $iIRsw$react.forwardRef)(function ColorWheel(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $9ce2d9a18460c1c0$export$265015d6dc85bf21);
    let state = (0, $iIRsw$reactstatelyuseColorWheelState.useColorWheelState)(props);
    let inputRef = (0, $iIRsw$react.useRef)(null);
    let { trackProps: trackProps, inputProps: inputProps, thumbProps: thumbProps } = (0, $iIRsw$reactariauseColorWheel.useColorWheel)(props, state, inputRef);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
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
    let DOMProps = (0, $iIRsw$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($iIRsw$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($iIRsw$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $9ce2d9a18460c1c0$export$f5327df9fc840d47,
                state
            ],
            [
                $9ce2d9a18460c1c0$export$aec8299548648839,
                trackProps
            ],
            [
                (0, $e8b1af84c136cd43$exports.InternalColorThumbContext),
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
const $9ce2d9a18460c1c0$export$aec8299548648839 = /*#__PURE__*/ (0, $iIRsw$react.createContext)(null);
const $9ce2d9a18460c1c0$export$aaae3dd1f909c692 = /*#__PURE__*/ (0, $iIRsw$react.forwardRef)(function ColorWheelTrack(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $9ce2d9a18460c1c0$export$aec8299548648839);
    let state = (0, $iIRsw$react.useContext)($9ce2d9a18460c1c0$export$f5327df9fc840d47);
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    let { className: className, style: style, ...rest } = props;
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-ColorWheelTrack',
        values: {
            isDisabled: state.isDisabled,
            state: state
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($iIRsw$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...rest,
        ...renderProps,
        ref: ref,
        "data-disabled": state.isDisabled || undefined
    });
});


//# sourceMappingURL=ColorWheel.cjs.map
