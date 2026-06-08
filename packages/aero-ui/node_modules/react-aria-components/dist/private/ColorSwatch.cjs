var $048d76b84370f141$exports = require("./utils.cjs");
var $a2xCh$reactariauseColorSwatch = require("react-aria/useColorSwatch");
var $a2xCh$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $a2xCh$reactariamergeProps = require("react-aria/mergeProps");
var $a2xCh$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ColorSwatchContext", function () { return $144cc1383f65bbfe$export$83cc445538396800; });
$parcel$export(module.exports, "ColorSwatch", function () { return $144cc1383f65bbfe$export$cae13e90592f246a; });





const $144cc1383f65bbfe$export$83cc445538396800 = /*#__PURE__*/ (0, $a2xCh$react.createContext)(null);
const $144cc1383f65bbfe$export$cae13e90592f246a = /*#__PURE__*/ (0, $a2xCh$react.forwardRef)(function ColorSwatch(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $144cc1383f65bbfe$export$83cc445538396800);
    let { colorSwatchProps: colorSwatchProps, color: color } = (0, $a2xCh$reactariauseColorSwatch.useColorSwatch)(props);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultClassName: 'react-aria-ColorSwatch',
        defaultStyle: colorSwatchProps.style,
        values: {
            color: color
        }
    });
    let DOMProps = (0, $a2xCh$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($a2xCh$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $a2xCh$reactariamergeProps.mergeProps)(DOMProps, colorSwatchProps, renderProps),
        slot: props.slot || undefined,
        ref: ref
    });
});


//# sourceMappingURL=ColorSwatch.cjs.map
