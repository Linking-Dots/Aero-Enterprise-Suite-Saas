import {composeRenderProps as $b7b7a92703138c9b$export$c245e6201fed2f75, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415} from "./utils.js";
import {ColorSwatchContext as $2945c4591e267b33$export$83cc445538396800} from "./ColorSwatch.js";
import $4uNKE$intlStringsjs from "./intlStrings.js";
import {ListBox as $ba3142315b3e1149$export$41f133550aa26f48, ListBoxItem as $ba3142315b3e1149$export$a11e76429ed99b4} from "./ListBox.js";
import {filterDOMProps as $4uNKE$filterDOMProps} from "react-aria/filterDOMProps";
import {parseColor as $4uNKE$parseColor} from "react-stately/Color";
import $4uNKE$react, {createContext as $4uNKE$createContext, forwardRef as $4uNKE$forwardRef, useMemo as $4uNKE$useMemo, useContext as $4uNKE$useContext, useEffect as $4uNKE$useEffect} from "react";
import {useColorPickerState as $4uNKE$useColorPickerState} from "react-stately/useColorPickerState";
import {useLocale as $4uNKE$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $4uNKE$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}










const $8a52f212098a563d$export$7214f50881fc1eaf = /*#__PURE__*/ (0, $4uNKE$createContext)(null);
const $8a52f212098a563d$var$ColorMapContext = /*#__PURE__*/ (0, $4uNKE$createContext)(null);
const $8a52f212098a563d$export$b46792416e3d8515 = /*#__PURE__*/ (0, $4uNKE$forwardRef)(function ColorSwatchPicker(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $8a52f212098a563d$export$7214f50881fc1eaf);
    let state = (0, $4uNKE$useColorPickerState)(props);
    let colorMap = (0, $4uNKE$useMemo)(()=>new Map(), []);
    let formatter = (0, $4uNKE$useLocalizedStringFormatter)((0, ($parcel$interopDefault($4uNKE$intlStringsjs))), 'react-aria-components');
    return /*#__PURE__*/ (0, $4uNKE$react).createElement((0, $ba3142315b3e1149$export$41f133550aa26f48), {
        ...(0, $4uNKE$filterDOMProps)(props, {
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
    }, /*#__PURE__*/ (0, $4uNKE$react).createElement($8a52f212098a563d$var$ColorMapContext.Provider, {
        value: colorMap
    }, props.children));
});
const $8a52f212098a563d$export$abcd89c27081c2ef = /*#__PURE__*/ (0, $4uNKE$forwardRef)(function ColorSwatchPickerItem(props, ref) {
    let propColor = props.color || '#0000';
    let color = (0, $4uNKE$useMemo)(()=>typeof propColor === 'string' ? (0, $4uNKE$parseColor)(propColor) : propColor, [
        propColor
    ]);
    let { locale: locale } = (0, $4uNKE$useLocale)();
    let map = (0, $4uNKE$useContext)($8a52f212098a563d$var$ColorMapContext);
    (0, $4uNKE$useEffect)(()=>{
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
    return /*#__PURE__*/ (0, $4uNKE$react).createElement((0, $ba3142315b3e1149$export$a11e76429ed99b4), {
        ...props,
        // ColorSwatchPickerItem is never a link.
        render: props.render,
        ref: ref,
        id: color.toString('hexa'),
        textValue: color.getColorName(locale),
        className: wrap(props.className || 'react-aria-ColorSwatchPickerItem'),
        style: wrap(props.style)
    }, (0, $b7b7a92703138c9b$export$c245e6201fed2f75)(wrap(props.children), (children)=>/*#__PURE__*/ (0, $4uNKE$react).createElement((0, $2945c4591e267b33$export$83cc445538396800).Provider, {
            value: {
                color: color
            }
        }, children)));
});


export {$8a52f212098a563d$export$7214f50881fc1eaf as ColorSwatchPickerContext, $8a52f212098a563d$export$b46792416e3d8515 as ColorSwatchPicker, $8a52f212098a563d$export$abcd89c27081c2ef as ColorSwatchPickerItem};
//# sourceMappingURL=ColorSwatchPicker.js.map
