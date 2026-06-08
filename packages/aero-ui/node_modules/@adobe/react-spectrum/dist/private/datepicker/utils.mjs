import {createDOMRef as $3c2c983d5210446c$export$a5795cc979dfae80} from "../utils/useDOMRef.mjs";
import {useProvider as $71dfb0e0358a12de$export$693cdb10cec23617} from "../provider/Provider.mjs";
import {createFocusManager as $dbF5F$createFocusManager} from "react-aria/private/focus/FocusScope";
import $dbF5F$react, {useMemo as $dbF5F$useMemo, useState as $dbF5F$useState, useRef as $dbF5F$useRef, useImperativeHandle as $dbF5F$useImperativeHandle} from "react";
import {useDateFormatter as $dbF5F$useDateFormatter} from "react-aria/useDateFormatter";
import {useDisplayNames as $dbF5F$useDisplayNames} from "react-aria/private/datepicker/useDisplayNames";
import {useLayoutEffect as $dbF5F$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useLocale as $dbF5F$useLocale} from "react-aria/I18nProvider";

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







function $d24c665d02225161$export$322f4580ccd8dde6(props) {
    let formatter = (0, $dbF5F$useDateFormatter)({
        dateStyle: 'short'
    });
    let displayNames = (0, $dbF5F$useDisplayNames)();
    return (0, $dbF5F$useMemo)(()=>{
        if (props.description) return props.description;
        if (props.showFormatHelpText) return formatter.formatToParts(new Date()).map((s, i)=>{
            if (s.type === 'literal' || s.type === 'unknown' || s.type === 'yearName') return /*#__PURE__*/ (0, $dbF5F$react).createElement("span", {
                key: i
            }, ` ${s.value} `);
            let type = s.type === 'relatedYear' ? 'year' : s.type;
            return /*#__PURE__*/ (0, $dbF5F$react).createElement("span", {
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
function $d24c665d02225161$export$12ce2869ce471b1f(maxVisibleMonths) {
    let { scale: scale } = (0, $71dfb0e0358a12de$export$693cdb10cec23617)();
    let [visibleMonths, setVisibleMonths] = (0, $dbF5F$useState)($d24c665d02225161$var$getVisibleMonths(scale));
    (0, $dbF5F$useLayoutEffect)(()=>{
        let onResize = ()=>setVisibleMonths($d24c665d02225161$var$getVisibleMonths(scale));
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
function $d24c665d02225161$var$getVisibleMonths(scale) {
    if (typeof window === 'undefined') return 1;
    let monthWidth = scale === 'large' ? 336 : 280;
    let gap = scale === 'large' ? 30 : 24;
    let popoverPadding = scale === 'large' ? 32 : 48;
    return Math.floor((window.innerWidth - popoverPadding * 2) / (monthWidth + gap));
}
function $d24c665d02225161$export$71a23a36270e4bf0(ref) {
    let domRef = (0, $dbF5F$useRef)(null);
    (0, $dbF5F$useImperativeHandle)(ref, ()=>({
            ...(0, $3c2c983d5210446c$export$a5795cc979dfae80)(domRef),
            focus () {
                (0, $dbF5F$createFocusManager)(domRef).focusFirst({
                    tabbable: true
                });
            }
        }));
    return domRef;
}
function $d24c665d02225161$export$31e22e3c931fc056(state) {
    let locale = (0, $dbF5F$useLocale)()?.locale;
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


export {$d24c665d02225161$export$322f4580ccd8dde6 as useFormatHelpText, $d24c665d02225161$export$12ce2869ce471b1f as useVisibleMonths, $d24c665d02225161$export$71a23a36270e4bf0 as useFocusManagerRef, $d24c665d02225161$export$31e22e3c931fc056 as useFormattedDateWidth};
//# sourceMappingURL=utils.mjs.map
