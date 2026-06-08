import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8} from "./utils.js";
import {InternalColorThumbContext as $80f3336a74d25baa$export$c80c0ea2ca5cb846} from "./ColorThumb.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {SliderOutputContext as $5f6ec8ec99e46675$export$6189c2744041d8f8, SliderStateContext as $5f6ec8ec99e46675$export$1e7083018727fa60, SliderTrackContext as $5f6ec8ec99e46675$export$f1fce0420cc6d8ee} from "./Slider.js";
import {useColorSlider as $dRuY0$useColorSlider} from "react-aria/useColorSlider";
import {useColorSliderState as $dRuY0$useColorSliderState} from "react-stately/useColorSliderState";
import {filterDOMProps as $dRuY0$filterDOMProps} from "react-aria/filterDOMProps";
import $dRuY0$react, {createContext as $dRuY0$createContext, forwardRef as $dRuY0$forwardRef} from "react";
import {useLocale as $dRuY0$useLocale} from "react-aria/I18nProvider";










const $957c6e562facdeae$export$717b2c0a523a0b53 = /*#__PURE__*/ (0, $dRuY0$createContext)(null);
const $957c6e562facdeae$export$c7fad7ea00194428 = /*#__PURE__*/ (0, $dRuY0$createContext)(null);
const $957c6e562facdeae$export$44fd664bcca5b6fb = /*#__PURE__*/ (0, $dRuY0$forwardRef)(function ColorSlider(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $957c6e562facdeae$export$717b2c0a523a0b53);
    let { locale: locale } = (0, $dRuY0$useLocale)();
    let state = (0, $dRuY0$useColorSliderState)({
        ...props,
        locale: locale
    });
    let trackRef = (0, $dRuY0$react).useRef(null);
    let inputRef = (0, $dRuY0$react).useRef(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { trackProps: trackProps, thumbProps: thumbProps, inputProps: inputProps, labelProps: labelProps, outputProps: outputProps } = (0, $dRuY0$useColorSlider)({
        ...props,
        label: label,
        trackRef: trackRef,
        inputRef: inputRef
    }, state);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ColorSlider'
    });
    let DOMProps = (0, $dRuY0$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $dRuY0$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $957c6e562facdeae$export$c7fad7ea00194428,
                state
            ],
            [
                (0, $5f6ec8ec99e46675$export$1e7083018727fa60),
                state
            ],
            [
                (0, $5f6ec8ec99e46675$export$f1fce0420cc6d8ee),
                {
                    ...trackProps,
                    ref: trackRef
                }
            ],
            [
                (0, $5f6ec8ec99e46675$export$6189c2744041d8f8),
                outputProps
            ],
            [
                (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    children: state.value.getChannelName(props.channel, locale)
                }
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
    }, /*#__PURE__*/ (0, $dRuY0$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": state.orientation,
        "data-disabled": state.isDisabled || undefined
    }));
});


export {$957c6e562facdeae$export$717b2c0a523a0b53 as ColorSliderContext, $957c6e562facdeae$export$c7fad7ea00194428 as ColorSliderStateContext, $957c6e562facdeae$export$44fd664bcca5b6fb as ColorSlider};
//# sourceMappingURL=ColorSlider.js.map
