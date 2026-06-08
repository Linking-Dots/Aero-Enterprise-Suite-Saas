import "./ColorEditor.css";
import {ColorArea as $6600f74a2ea71e97$export$b2103f68a961418e} from "./ColorArea.mjs";
import {ColorField as $41f1a8db81de171a$export$b865d4358897bb17} from "./ColorField.mjs";
import {ColorSlider as $60ebf63a8fdaa76a$export$44fd664bcca5b6fb} from "./ColorSlider.mjs";
import $kw9qm$intlStringsmjs from "./intlStrings.mjs";
import {Picker as $933e5a05c989c3a1$export$ba25329847403e11} from "../picker/Picker.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {getColorChannels as $kw9qm$getColorChannels} from "react-stately/Color";
import {Item as $kw9qm$Item} from "react-stately/Item";
import $kw9qm$react, {useState as $kw9qm$useState} from "react";
import {useLocalizedStringFormatter as $kw9qm$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}










const $e4d53b74ba2d8800$export$5aa54fd21eb08d23 = /*#__PURE__*/ (0, $kw9qm$react).forwardRef(function ColorEditor(props, ref) {
    let [format, setFormat] = (0, $kw9qm$useState)('hex');
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let formatter = (0, $kw9qm$useLocalizedStringFormatter)((0, ($parcel$interopDefault($kw9qm$intlStringsmjs))), '@react-spectrum/color');
    return /*#__PURE__*/ (0, $kw9qm$react).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-_0s1-b';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }(),
        ref: domRef
    }, /*#__PURE__*/ (0, $kw9qm$react).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }()
    }, /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $6600f74a2ea71e97$export$b2103f68a961418e), {
        colorSpace: "hsb",
        xChannel: "saturation",
        yChannel: "brightness"
    }), /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $60ebf63a8fdaa76a$export$44fd664bcca5b6fb), {
        colorSpace: "hsb",
        channel: "hue",
        orientation: "vertical"
    }), !props.hideAlphaChannel && /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $60ebf63a8fdaa76a$export$44fd664bcca5b6fb), {
        channel: "alpha",
        orientation: "vertical"
    })), /*#__PURE__*/ (0, $kw9qm$react).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }()
    }, /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $933e5a05c989c3a1$export$ba25329847403e11), {
        "aria-label": formatter.format('colorFormat'),
        isQuiet: true,
        width: "size-700",
        menuWidth: "size-1000",
        selectedKey: format,
        onSelectionChange: (f)=>setFormat(f)
    }, /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $kw9qm$Item), {
        key: "hex"
    }, formatter.format('hex')), /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $kw9qm$Item), {
        key: "rgb"
    }, formatter.format('rgb')), /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $kw9qm$Item), {
        key: "hsl"
    }, formatter.format('hsl')), /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $kw9qm$Item), {
        key: "hsb"
    }, formatter.format('hsb'))), format === 'hex' ? /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $41f1a8db81de171a$export$b865d4358897bb17), {
        isQuiet: true,
        width: "size-1000",
        "aria-label": formatter.format('hex')
    }) : (0, $kw9qm$getColorChannels)(format).map((channel)=>/*#__PURE__*/ (0, $kw9qm$react).createElement((0, $41f1a8db81de171a$export$b865d4358897bb17), {
            key: channel,
            colorSpace: format,
            channel: channel,
            isQuiet: true,
            width: "size-400",
            flex: true,
            UNSAFE_style: {
                '--spectrum-textfield-min-width': 0
            }
        })), !props.hideAlphaChannel && /*#__PURE__*/ (0, $kw9qm$react).createElement((0, $41f1a8db81de171a$export$b865d4358897bb17), {
        channel: "alpha",
        isQuiet: true,
        width: "size-400",
        flex: true,
        UNSAFE_style: {
            '--spectrum-textfield-min-width': 0
        }
    })));
});


export {$e4d53b74ba2d8800$export$5aa54fd21eb08d23 as ColorEditor};
//# sourceMappingURL=ColorEditor.mjs.map
