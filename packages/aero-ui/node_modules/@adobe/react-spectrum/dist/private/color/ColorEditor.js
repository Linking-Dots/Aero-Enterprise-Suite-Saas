import "./ColorEditor.css";
import {ColorArea as $d6409d59c6d41934$export$b2103f68a961418e} from "./ColorArea.js";
import {ColorField as $5b07f9ba6aa1e89e$export$b865d4358897bb17} from "./ColorField.js";
import {ColorSlider as $5234de165996dd10$export$44fd664bcca5b6fb} from "./ColorSlider.js";
import $iVmcM$intlStringsjs from "./intlStrings.js";
import {Picker as $fcdeb62019c30c53$export$ba25329847403e11} from "../picker/Picker.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {getColorChannels as $iVmcM$getColorChannels} from "react-stately/Color";
import {Item as $iVmcM$Item} from "react-stately/Item";
import $iVmcM$react, {useState as $iVmcM$useState} from "react";
import {useLocalizedStringFormatter as $iVmcM$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}










const $464d9fb69b03568b$export$5aa54fd21eb08d23 = /*#__PURE__*/ (0, $iVmcM$react).forwardRef(function ColorEditor(props, ref) {
    let [format, setFormat] = (0, $iVmcM$useState)('hex');
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let formatter = (0, $iVmcM$useLocalizedStringFormatter)((0, ($parcel$interopDefault($iVmcM$intlStringsjs))), '@react-spectrum/color');
    return /*#__PURE__*/ (0, $iVmcM$react).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-_0s1-b';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }(),
        ref: domRef
    }, /*#__PURE__*/ (0, $iVmcM$react).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }()
    }, /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $d6409d59c6d41934$export$b2103f68a961418e), {
        colorSpace: "hsb",
        xChannel: "saturation",
        yChannel: "brightness"
    }), /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $5234de165996dd10$export$44fd664bcca5b6fb), {
        colorSpace: "hsb",
        channel: "hue",
        orientation: "vertical"
    }), !props.hideAlphaChannel && /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $5234de165996dd10$export$44fd664bcca5b6fb), {
        channel: "alpha",
        orientation: "vertical"
    })), /*#__PURE__*/ (0, $iVmcM$react).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }()
    }, /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $fcdeb62019c30c53$export$ba25329847403e11), {
        "aria-label": formatter.format('colorFormat'),
        isQuiet: true,
        width: "size-700",
        menuWidth: "size-1000",
        selectedKey: format,
        onSelectionChange: (f)=>setFormat(f)
    }, /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $iVmcM$Item), {
        key: "hex"
    }, formatter.format('hex')), /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $iVmcM$Item), {
        key: "rgb"
    }, formatter.format('rgb')), /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $iVmcM$Item), {
        key: "hsl"
    }, formatter.format('hsl')), /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $iVmcM$Item), {
        key: "hsb"
    }, formatter.format('hsb'))), format === 'hex' ? /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $5b07f9ba6aa1e89e$export$b865d4358897bb17), {
        isQuiet: true,
        width: "size-1000",
        "aria-label": formatter.format('hex')
    }) : (0, $iVmcM$getColorChannels)(format).map((channel)=>/*#__PURE__*/ (0, $iVmcM$react).createElement((0, $5b07f9ba6aa1e89e$export$b865d4358897bb17), {
            key: channel,
            colorSpace: format,
            channel: channel,
            isQuiet: true,
            width: "size-400",
            flex: true,
            UNSAFE_style: {
                '--spectrum-textfield-min-width': 0
            }
        })), !props.hideAlphaChannel && /*#__PURE__*/ (0, $iVmcM$react).createElement((0, $5b07f9ba6aa1e89e$export$b865d4358897bb17), {
        channel: "alpha",
        isQuiet: true,
        width: "size-400",
        flex: true,
        UNSAFE_style: {
            '--spectrum-textfield-min-width': 0
        }
    })));
});


export {$464d9fb69b03568b$export$5aa54fd21eb08d23 as ColorEditor};
//# sourceMappingURL=ColorEditor.js.map
