var $048d76b84370f141$exports = require("./utils.cjs");
var $e8b1af84c136cd43$exports = require("./ColorThumb.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $30cd4abd8dfcbef7$exports = require("./Slider.cjs");
var $3ZyKd$reactariauseColorSlider = require("react-aria/useColorSlider");
var $3ZyKd$reactstatelyuseColorSliderState = require("react-stately/useColorSliderState");
var $3ZyKd$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $3ZyKd$react = require("react");
var $3ZyKd$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorSliderContext", function () { return $2c508fe14bf948ee$export$717b2c0a523a0b53; });
$parcel$export(module.exports, "ColorSliderStateContext", function () { return $2c508fe14bf948ee$export$c7fad7ea00194428; });
$parcel$export(module.exports, "ColorSlider", function () { return $2c508fe14bf948ee$export$44fd664bcca5b6fb; });









const $2c508fe14bf948ee$export$717b2c0a523a0b53 = /*#__PURE__*/ (0, $3ZyKd$react.createContext)(null);
const $2c508fe14bf948ee$export$c7fad7ea00194428 = /*#__PURE__*/ (0, $3ZyKd$react.createContext)(null);
const $2c508fe14bf948ee$export$44fd664bcca5b6fb = /*#__PURE__*/ (0, $3ZyKd$react.forwardRef)(function ColorSlider(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $2c508fe14bf948ee$export$717b2c0a523a0b53);
    let { locale: locale } = (0, $3ZyKd$reactariaI18nProvider.useLocale)();
    let state = (0, $3ZyKd$reactstatelyuseColorSliderState.useColorSliderState)({
        ...props,
        locale: locale
    });
    let trackRef = (0, ($parcel$interopDefault($3ZyKd$react))).useRef(null);
    let inputRef = (0, ($parcel$interopDefault($3ZyKd$react))).useRef(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { trackProps: trackProps, thumbProps: thumbProps, inputProps: inputProps, labelProps: labelProps, outputProps: outputProps } = (0, $3ZyKd$reactariauseColorSlider.useColorSlider)({
        ...props,
        label: label,
        trackRef: trackRef,
        inputRef: inputRef
    }, state);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ColorSlider'
    });
    let DOMProps = (0, $3ZyKd$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($3ZyKd$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $2c508fe14bf948ee$export$c7fad7ea00194428,
                state
            ],
            [
                (0, $30cd4abd8dfcbef7$exports.SliderStateContext),
                state
            ],
            [
                (0, $30cd4abd8dfcbef7$exports.SliderTrackContext),
                {
                    ...trackProps,
                    ref: trackRef
                }
            ],
            [
                (0, $30cd4abd8dfcbef7$exports.SliderOutputContext),
                outputProps
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef,
                    children: state.value.getChannelName(props.channel, locale)
                }
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
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($3ZyKd$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": state.orientation,
        "data-disabled": state.isDisabled || undefined
    }));
});


//# sourceMappingURL=ColorSlider.cjs.map
