var $048d76b84370f141$exports = require("./utils.cjs");
var $144cc1383f65bbfe$exports = require("./ColorSwatch.cjs");
var $5724b511a2687756$exports = require("./intlStrings.cjs");
var $537333b300f7e667$exports = require("./ListBox.cjs");
var $hFlJy$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $hFlJy$reactstatelyColor = require("react-stately/Color");
var $hFlJy$react = require("react");
var $hFlJy$reactstatelyuseColorPickerState = require("react-stately/useColorPickerState");
var $hFlJy$reactariaI18nProvider = require("react-aria/I18nProvider");
var $hFlJy$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorSwatchPickerContext", function () { return $b3b4abadd57cf07d$export$7214f50881fc1eaf; });
$parcel$export(module.exports, "ColorSwatchPicker", function () { return $b3b4abadd57cf07d$export$b46792416e3d8515; });
$parcel$export(module.exports, "ColorSwatchPickerItem", function () { return $b3b4abadd57cf07d$export$abcd89c27081c2ef; });










const $b3b4abadd57cf07d$export$7214f50881fc1eaf = /*#__PURE__*/ (0, $hFlJy$react.createContext)(null);
const $b3b4abadd57cf07d$var$ColorMapContext = /*#__PURE__*/ (0, $hFlJy$react.createContext)(null);
const $b3b4abadd57cf07d$export$b46792416e3d8515 = /*#__PURE__*/ (0, $hFlJy$react.forwardRef)(function ColorSwatchPicker(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $b3b4abadd57cf07d$export$7214f50881fc1eaf);
    let state = (0, $hFlJy$reactstatelyuseColorPickerState.useColorPickerState)(props);
    let colorMap = (0, $hFlJy$react.useMemo)(()=>new Map(), []);
    let formatter = (0, $hFlJy$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($5724b511a2687756$exports))), 'react-aria-components');
    return /*#__PURE__*/ (0, ($parcel$interopDefault($hFlJy$react))).createElement((0, $537333b300f7e667$exports.ListBox), {
        ...(0, $hFlJy$reactariafilterDOMProps.filterDOMProps)(props, {
            labelable: true
        }),
        ref: ref,
        className: props.className || 'react-aria-ColorSwatchPicker',
        style: props.style,
        "aria-label": props['aria-label'] || (!props['aria-labelledby'] ? formatter.format('colorSwatchPicker') : undefined),
        layout: props.layout || 'grid',
        selectionMode: "single",
        selectedKeys: [
            state.color.toString('hexa')
        ],
        onSelectionChange: (keys)=>{
            // single select, 'all' cannot occur. appease typescript.
            if (keys !== 'all') state.setColor(colorMap.get([
                ...keys
            ][0]));
        },
        disallowEmptySelection: true
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hFlJy$react))).createElement($b3b4abadd57cf07d$var$ColorMapContext.Provider, {
        value: colorMap
    }, props.children));
});
const $b3b4abadd57cf07d$export$abcd89c27081c2ef = /*#__PURE__*/ (0, $hFlJy$react.forwardRef)(function ColorSwatchPickerItem(props, ref) {
    let propColor = props.color || '#0000';
    let color = (0, $hFlJy$react.useMemo)(()=>typeof propColor === 'string' ? (0, $hFlJy$reactstatelyColor.parseColor)(propColor) : propColor, [
        propColor
    ]);
    let { locale: locale } = (0, $hFlJy$reactariaI18nProvider.useLocale)();
    let map = (0, $hFlJy$react.useContext)($b3b4abadd57cf07d$var$ColorMapContext);
    (0, $hFlJy$react.useEffect)(()=>{
        let key = color.toString('hexa');
        map.set(key, color);
        return ()=>{
            map.delete(key);
        };
    }, [
        color,
        map
    ]);
    let wrap = (v)=>{
        if (typeof v === 'function') return (renderProps)=>v({
                ...renderProps,
                color: color
            });
        return v;
    };
    return /*#__PURE__*/ (0, ($parcel$interopDefault($hFlJy$react))).createElement((0, $537333b300f7e667$exports.ListBoxItem), {
        ...props,
        // ColorSwatchPickerItem is never a link.
        render: props.render,
        ref: ref,
        id: color.toString('hexa'),
        textValue: color.getColorName(locale),
        className: wrap(props.className || 'react-aria-ColorSwatchPickerItem'),
        style: wrap(props.style)
    }, (0, $048d76b84370f141$exports.composeRenderProps)(wrap(props.children), (children)=>/*#__PURE__*/ (0, ($parcel$interopDefault($hFlJy$react))).createElement((0, $144cc1383f65bbfe$exports.ColorSwatchContext).Provider, {
            value: {
                color: color
            }
        }, children)));
});


//# sourceMappingURL=ColorSwatchPicker.cjs.map
