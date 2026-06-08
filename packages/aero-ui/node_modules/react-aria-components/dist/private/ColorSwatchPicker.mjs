import {composeRenderProps as $7230ffa83bc0c2cf$export$c245e6201fed2f75, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415} from "./utils.mjs";
import {ColorSwatchContext as $eeaff5a2d2421ecc$export$83cc445538396800} from "./ColorSwatch.mjs";
import $kuZDw$intlStringsmjs from "./intlStrings.mjs";
import {ListBox as $928221da08ecbc62$export$41f133550aa26f48, ListBoxItem as $928221da08ecbc62$export$a11e76429ed99b4} from "./ListBox.mjs";
import {filterDOMProps as $kuZDw$filterDOMProps} from "react-aria/filterDOMProps";
import {parseColor as $kuZDw$parseColor} from "react-stately/Color";
import $kuZDw$react, {createContext as $kuZDw$createContext, forwardRef as $kuZDw$forwardRef, useMemo as $kuZDw$useMemo, useContext as $kuZDw$useContext, useEffect as $kuZDw$useEffect} from "react";
import {useColorPickerState as $kuZDw$useColorPickerState} from "react-stately/useColorPickerState";
import {useLocale as $kuZDw$useLocale} from "react-aria/I18nProvider";
import {useLocalizedStringFormatter as $kuZDw$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}










const $a36727cf8b43b57f$export$7214f50881fc1eaf = /*#__PURE__*/ (0, $kuZDw$createContext)(null);
const $a36727cf8b43b57f$var$ColorMapContext = /*#__PURE__*/ (0, $kuZDw$createContext)(null);
const $a36727cf8b43b57f$export$b46792416e3d8515 = /*#__PURE__*/ (0, $kuZDw$forwardRef)(function ColorSwatchPicker(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $a36727cf8b43b57f$export$7214f50881fc1eaf);
    let state = (0, $kuZDw$useColorPickerState)(props);
    let colorMap = (0, $kuZDw$useMemo)(()=>new Map(), []);
    let formatter = (0, $kuZDw$useLocalizedStringFormatter)((0, ($parcel$interopDefault($kuZDw$intlStringsmjs))), 'react-aria-components');
    return /*#__PURE__*/ (0, $kuZDw$react).createElement((0, $928221da08ecbc62$export$41f133550aa26f48), {
        ...(0, $kuZDw$filterDOMProps)(props, {
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
    }, /*#__PURE__*/ (0, $kuZDw$react).createElement($a36727cf8b43b57f$var$ColorMapContext.Provider, {
        value: colorMap
    }, props.children));
});
const $a36727cf8b43b57f$export$abcd89c27081c2ef = /*#__PURE__*/ (0, $kuZDw$forwardRef)(function ColorSwatchPickerItem(props, ref) {
    let propColor = props.color || '#0000';
    let color = (0, $kuZDw$useMemo)(()=>typeof propColor === 'string' ? (0, $kuZDw$parseColor)(propColor) : propColor, [
        propColor
    ]);
    let { locale: locale } = (0, $kuZDw$useLocale)();
    let map = (0, $kuZDw$useContext)($a36727cf8b43b57f$var$ColorMapContext);
    (0, $kuZDw$useEffect)(()=>{
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
    return /*#__PURE__*/ (0, $kuZDw$react).createElement((0, $928221da08ecbc62$export$a11e76429ed99b4), {
        ...props,
        // ColorSwatchPickerItem is never a link.
        render: props.render,
        ref: ref,
        id: color.toString('hexa'),
        textValue: color.getColorName(locale),
        className: wrap(props.className || 'react-aria-ColorSwatchPickerItem'),
        style: wrap(props.style)
    }, (0, $7230ffa83bc0c2cf$export$c245e6201fed2f75)(wrap(props.children), (children)=>/*#__PURE__*/ (0, $kuZDw$react).createElement((0, $eeaff5a2d2421ecc$export$83cc445538396800).Provider, {
            value: {
                color: color
            }
        }, children)));
});


export {$a36727cf8b43b57f$export$7214f50881fc1eaf as ColorSwatchPickerContext, $a36727cf8b43b57f$export$b46792416e3d8515 as ColorSwatchPicker, $a36727cf8b43b57f$export$abcd89c27081c2ef as ColorSwatchPickerItem};
//# sourceMappingURL=ColorSwatchPicker.mjs.map
