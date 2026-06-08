import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8} from "./utils.mjs";
import {InternalColorThumbContext as $ceafedee624ffe11$export$c80c0ea2ca5cb846} from "./ColorThumb.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {SliderOutputContext as $806e559d0c2c64a0$export$6189c2744041d8f8, SliderStateContext as $806e559d0c2c64a0$export$1e7083018727fa60, SliderTrackContext as $806e559d0c2c64a0$export$f1fce0420cc6d8ee} from "./Slider.mjs";
import {useColorSlider as $egeNW$useColorSlider} from "react-aria/useColorSlider";
import {useColorSliderState as $egeNW$useColorSliderState} from "react-stately/useColorSliderState";
import {filterDOMProps as $egeNW$filterDOMProps} from "react-aria/filterDOMProps";
import $egeNW$react, {createContext as $egeNW$createContext, forwardRef as $egeNW$forwardRef} from "react";
import {useLocale as $egeNW$useLocale} from "react-aria/I18nProvider";










const $016f94378f03b8fe$export$717b2c0a523a0b53 = /*#__PURE__*/ (0, $egeNW$createContext)(null);
const $016f94378f03b8fe$export$c7fad7ea00194428 = /*#__PURE__*/ (0, $egeNW$createContext)(null);
const $016f94378f03b8fe$export$44fd664bcca5b6fb = /*#__PURE__*/ (0, $egeNW$forwardRef)(function ColorSlider(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $016f94378f03b8fe$export$717b2c0a523a0b53);
    let { locale: locale } = (0, $egeNW$useLocale)();
    let state = (0, $egeNW$useColorSliderState)({
        ...props,
        locale: locale
    });
    let trackRef = (0, $egeNW$react).useRef(null);
    let inputRef = (0, $egeNW$react).useRef(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { trackProps: trackProps, thumbProps: thumbProps, inputProps: inputProps, labelProps: labelProps, outputProps: outputProps } = (0, $egeNW$useColorSlider)({
        ...props,
        label: label,
        trackRef: trackRef,
        inputRef: inputRef
    }, state);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            orientation: state.orientation,
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ColorSlider'
    });
    let DOMProps = (0, $egeNW$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, $egeNW$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $016f94378f03b8fe$export$c7fad7ea00194428,
                state
            ],
            [
                (0, $806e559d0c2c64a0$export$1e7083018727fa60),
                state
            ],
            [
                (0, $806e559d0c2c64a0$export$f1fce0420cc6d8ee),
                {
                    ...trackProps,
                    ref: trackRef
                }
            ],
            [
                (0, $806e559d0c2c64a0$export$6189c2744041d8f8),
                outputProps
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    children: state.value.getChannelName(props.channel, locale)
                }
            ],
            [
                (0, $ceafedee624ffe11$export$c80c0ea2ca5cb846),
                {
                    state: state,
                    thumbProps: thumbProps,
                    inputXRef: inputRef,
                    xInputProps: inputProps,
                    isDisabled: props.isDisabled
                }
            ]
        ]
    }, /*#__PURE__*/ (0, $egeNW$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...DOMProps,
        ...renderProps,
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": state.orientation,
        "data-disabled": state.isDisabled || undefined
    }));
});


export {$016f94378f03b8fe$export$717b2c0a523a0b53 as ColorSliderContext, $016f94378f03b8fe$export$c7fad7ea00194428 as ColorSliderStateContext, $016f94378f03b8fe$export$44fd664bcca5b6fb as ColorSlider};
//# sourceMappingURL=ColorSlider.mjs.map
