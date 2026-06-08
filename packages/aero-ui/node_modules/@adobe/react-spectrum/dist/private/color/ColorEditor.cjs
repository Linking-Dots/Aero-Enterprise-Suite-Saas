require("./ColorEditor.css");
var $74f6990c4ec74329$exports = require("./ColorArea.cjs");
var $cd43964140eb8bb2$exports = require("./ColorField.cjs");
var $3b70a976df9d5d9d$exports = require("./ColorSlider.cjs");
var $acda6699a7397e05$exports = require("./intlStrings.cjs");
var $4ab2867caa392e8e$exports = require("../picker/Picker.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $6xD7f$reactstatelyColor = require("react-stately/Color");
var $6xD7f$reactstatelyItem = require("react-stately/Item");
var $6xD7f$react = require("react");
var $6xD7f$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorEditor", function () { return $2d608f7049c8c2f2$export$5aa54fd21eb08d23; });










const $2d608f7049c8c2f2$export$5aa54fd21eb08d23 = /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).forwardRef(function ColorEditor(props, ref) {
    let [format, setFormat] = (0, $6xD7f$react.useState)('hex');
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let formatter = (0, $6xD7f$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($acda6699a7397e05$exports))), '@react-spectrum/color');
    return /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-_0s1-b';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }(),
        ref: domRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }()
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $74f6990c4ec74329$exports.ColorArea), {
        colorSpace: "hsb",
        xChannel: "saturation",
        yChannel: "brightness"
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $3b70a976df9d5d9d$exports.ColorSlider), {
        colorSpace: "hsb",
        channel: "hue",
        orientation: "vertical"
    }), !props.hideAlphaChannel && /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $3b70a976df9d5d9d$exports.ColorSlider), {
        channel: "alpha",
        orientation: "vertical"
    })), /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement("div", {
        className: function anonymous(props) {
            let rules = "";
            rules += ' s1-_Ts1-d';
            rules += ' s1-ls1-e';
            rules += ' s1-ms1-e';
            return rules;
        }()
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $4ab2867caa392e8e$exports.Picker), {
        "aria-label": formatter.format('colorFormat'),
        isQuiet: true,
        width: "size-700",
        menuWidth: "size-1000",
        selectedKey: format,
        onSelectionChange: (f)=>setFormat(f)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $6xD7f$reactstatelyItem.Item), {
        key: "hex"
    }, formatter.format('hex')), /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $6xD7f$reactstatelyItem.Item), {
        key: "rgb"
    }, formatter.format('rgb')), /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $6xD7f$reactstatelyItem.Item), {
        key: "hsl"
    }, formatter.format('hsl')), /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $6xD7f$reactstatelyItem.Item), {
        key: "hsb"
    }, formatter.format('hsb'))), format === 'hex' ? /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $cd43964140eb8bb2$exports.ColorField), {
        isQuiet: true,
        width: "size-1000",
        "aria-label": formatter.format('hex')
    }) : (0, $6xD7f$reactstatelyColor.getColorChannels)(format).map((channel)=>/*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $cd43964140eb8bb2$exports.ColorField), {
            key: channel,
            colorSpace: format,
            channel: channel,
            isQuiet: true,
            width: "size-400",
            flex: true,
            UNSAFE_style: {
                '--spectrum-textfield-min-width': 0
            }
        })), !props.hideAlphaChannel && /*#__PURE__*/ (0, ($parcel$interopDefault($6xD7f$react))).createElement((0, $cd43964140eb8bb2$exports.ColorField), {
        channel: "alpha",
        isQuiet: true,
        width: "size-400",
        flex: true,
        UNSAFE_style: {
            '--spectrum-textfield-min-width': 0
        }
    })));
});


//# sourceMappingURL=ColorEditor.cjs.map
