import {createDOMRef as $c234463e9ef56637$export$a5795cc979dfae80} from "../utils/useDOMRef.js";
import {useProvider as $089943c7a219141c$export$693cdb10cec23617} from "../provider/Provider.js";
import {createFocusManager as $5R3rd$createFocusManager} from "react-aria/private/focus/FocusScope";
import $5R3rd$react, {useMemo as $5R3rd$useMemo, useState as $5R3rd$useState, useRef as $5R3rd$useRef, useImperativeHandle as $5R3rd$useImperativeHandle} from "react";
import {useDateFormatter as $5R3rd$useDateFormatter} from "react-aria/useDateFormatter";
import {useDisplayNames as $5R3rd$useDisplayNames} from "react-aria/private/datepicker/useDisplayNames";
import {useLayoutEffect as $5R3rd$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useLocale as $5R3rd$useLocale} from "react-aria/I18nProvider";

/*
 * Copyright 2020 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 







function $14b5acfdaf2344b2$export$322f4580ccd8dde6(props) {
    let formatter = (0, $5R3rd$useDateFormatter)({
        dateStyle: 'short'
    });
    let displayNames = (0, $5R3rd$useDisplayNames)();
    return (0, $5R3rd$useMemo)(()=>{
        if (props.description) return props.description;
        if (props.showFormatHelpText) return formatter.formatToParts(new Date()).map((s, i)=>{
            if (s.type === 'literal' || s.type === 'unknown' || s.type === 'yearName') return /*#__PURE__*/ (0, $5R3rd$react).createElement("span", {
                key: i
            }, ` ${s.value} `);
            let type = s.type === 'relatedYear' ? 'year' : s.type;
            return /*#__PURE__*/ (0, $5R3rd$react).createElement("span", {
                key: i,
                style: {
                    unicodeBidi: 'embed',
                    direction: 'ltr'
                }
            }, displayNames.of(type));
        });
        return '';
    }, [
        props.description,
        props.showFormatHelpText,
        formatter,
        displayNames
    ]);
}
function $14b5acfdaf2344b2$export$12ce2869ce471b1f(maxVisibleMonths) {
    let { scale: scale } = (0, $089943c7a219141c$export$693cdb10cec23617)();
    let [visibleMonths, setVisibleMonths] = (0, $5R3rd$useState)($14b5acfdaf2344b2$var$getVisibleMonths(scale));
    (0, $5R3rd$useLayoutEffect)(()=>{
        let onResize = ()=>setVisibleMonths($14b5acfdaf2344b2$var$getVisibleMonths(scale));
        onResize();
        window.addEventListener('resize', onResize);
        return ()=>{
            window.removeEventListener('resize', onResize);
        };
    }, [
        scale
    ]);
    return Math.max(1, Math.min(visibleMonths, maxVisibleMonths, 3));
}
function $14b5acfdaf2344b2$var$getVisibleMonths(scale) {
    if (typeof window === 'undefined') return 1;
    let monthWidth = scale === 'large' ? 336 : 280;
    let gap = scale === 'large' ? 30 : 24;
    let popoverPadding = scale === 'large' ? 32 : 48;
    return Math.floor((window.innerWidth - popoverPadding * 2) / (monthWidth + gap));
}
function $14b5acfdaf2344b2$export$71a23a36270e4bf0(ref) {
    let domRef = (0, $5R3rd$useRef)(null);
    (0, $5R3rd$useImperativeHandle)(ref, ()=>({
            ...(0, $c234463e9ef56637$export$a5795cc979dfae80)(domRef),
            focus () {
                (0, $5R3rd$createFocusManager)(domRef).focusFirst({
                    tabbable: true
                });
            }
        }));
    return domRef;
}
function $14b5acfdaf2344b2$export$31e22e3c931fc056(state) {
    var _useLocale;
    let locale = (_useLocale = (0, $5R3rd$useLocale)()) === null || _useLocale === void 0 ? void 0 : _useLocale.locale;
    let currentDate = new Date();
    let formatedDate = state.getDateFormatter(locale, {
        shouldForceLeadingZeros: true
    }).format(currentDate);
    let totalCharacters = formatedDate.length;
    // The max of two is for times with only hours.
    // As the length of a date grows we need to proportionally increase the width.
    // We use the character count with 'ch' units and add extra padding to accomate for
    // dates with months and time dashes, which are wider characters.
    return totalCharacters + Math.max(Math.floor(totalCharacters / 5), 2);
}


export {$14b5acfdaf2344b2$export$322f4580ccd8dde6 as useFormatHelpText, $14b5acfdaf2344b2$export$12ce2869ce471b1f as useVisibleMonths, $14b5acfdaf2344b2$export$71a23a36270e4bf0 as useFocusManagerRef, $14b5acfdaf2344b2$export$31e22e3c931fc056 as useFormattedDateWidth};
//# sourceMappingURL=utils.js.map
