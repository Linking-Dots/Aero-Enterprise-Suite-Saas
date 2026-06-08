var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $1e2Ln$reactariaprivatefocusFocusScope = require("react-aria/private/focus/FocusScope");
var $1e2Ln$react = require("react");
var $1e2Ln$reactariauseDateFormatter = require("react-aria/useDateFormatter");
var $1e2Ln$reactariaprivatedatepickeruseDisplayNames = require("react-aria/private/datepicker/useDisplayNames");
var $1e2Ln$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $1e2Ln$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "useFormatHelpText", function () { return $7f5eff3a70a58c6f$export$322f4580ccd8dde6; });
$parcel$export(module.exports, "useVisibleMonths", function () { return $7f5eff3a70a58c6f$export$12ce2869ce471b1f; });
$parcel$export(module.exports, "useFocusManagerRef", function () { return $7f5eff3a70a58c6f$export$71a23a36270e4bf0; });
$parcel$export(module.exports, "useFormattedDateWidth", function () { return $7f5eff3a70a58c6f$export$31e22e3c931fc056; });
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







function $7f5eff3a70a58c6f$export$322f4580ccd8dde6(props) {
    let formatter = (0, $1e2Ln$reactariauseDateFormatter.useDateFormatter)({
        dateStyle: 'short'
    });
    let displayNames = (0, $1e2Ln$reactariaprivatedatepickeruseDisplayNames.useDisplayNames)();
    return (0, $1e2Ln$react.useMemo)(()=>{
        if (props.description) return props.description;
        if (props.showFormatHelpText) return formatter.formatToParts(new Date()).map((s, i)=>{
            if (s.type === 'literal' || s.type === 'unknown' || s.type === 'yearName') return /*#__PURE__*/ (0, ($parcel$interopDefault($1e2Ln$react))).createElement("span", {
                key: i
            }, ` ${s.value} `);
            let type = s.type === 'relatedYear' ? 'year' : s.type;
            return /*#__PURE__*/ (0, ($parcel$interopDefault($1e2Ln$react))).createElement("span", {
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
function $7f5eff3a70a58c6f$export$12ce2869ce471b1f(maxVisibleMonths) {
    let { scale: scale } = (0, $544fc82701fc93e9$exports.useProvider)();
    let [visibleMonths, setVisibleMonths] = (0, $1e2Ln$react.useState)($7f5eff3a70a58c6f$var$getVisibleMonths(scale));
    (0, $1e2Ln$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(()=>{
        let onResize = ()=>setVisibleMonths($7f5eff3a70a58c6f$var$getVisibleMonths(scale));
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
function $7f5eff3a70a58c6f$var$getVisibleMonths(scale) {
    if (typeof window === 'undefined') return 1;
    let monthWidth = scale === 'large' ? 336 : 280;
    let gap = scale === 'large' ? 30 : 24;
    let popoverPadding = scale === 'large' ? 32 : 48;
    return Math.floor((window.innerWidth - popoverPadding * 2) / (monthWidth + gap));
}
function $7f5eff3a70a58c6f$export$71a23a36270e4bf0(ref) {
    let domRef = (0, $1e2Ln$react.useRef)(null);
    (0, $1e2Ln$react.useImperativeHandle)(ref, ()=>({
            ...(0, $65aea7b37663976b$exports.createDOMRef)(domRef),
            focus () {
                (0, $1e2Ln$reactariaprivatefocusFocusScope.createFocusManager)(domRef).focusFirst({
                    tabbable: true
                });
            }
        }));
    return domRef;
}
function $7f5eff3a70a58c6f$export$31e22e3c931fc056(state) {
    let locale = (0, $1e2Ln$reactariaI18nProvider.useLocale)()?.locale;
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


//# sourceMappingURL=utils.cjs.map
