var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $d6479700d21b596b$exports = require("../layout/Grid.cjs");
var $848c7665a935e829$exports = require("./intlStrings.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../inlinealert_vars.css");
var $6bfd7569368bc154$exports = require("../inlinealert_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $4izoM$spectrumiconsuiAlertMedium = require("@spectrum-icons/ui/AlertMedium");
var $4izoM$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $4izoM$reactariaFocusRing = require("react-aria/FocusRing");
var $4izoM$spectrumiconsuiInfoMedium = require("@spectrum-icons/ui/InfoMedium");
var $4izoM$react = require("react");
var $4izoM$spectrumiconsuiSuccessMedium = require("@spectrum-icons/ui/SuccessMedium");
var $4izoM$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "InlineAlert", function () { return $ac5a0c58210bd748$export$a3b2c96db9b0eb71; });
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 














let $ac5a0c58210bd748$var$ICONS = {
    info: (0, ($parcel$interopDefault($4izoM$spectrumiconsuiInfoMedium))),
    positive: (0, ($parcel$interopDefault($4izoM$spectrumiconsuiSuccessMedium))),
    notice: (0, ($parcel$interopDefault($4izoM$spectrumiconsuiAlertMedium))),
    negative: (0, ($parcel$interopDefault($4izoM$spectrumiconsuiAlertMedium)))
};
const $ac5a0c58210bd748$export$a3b2c96db9b0eb71 = /*#__PURE__*/ (0, ($parcel$interopDefault($4izoM$react))).forwardRef(function InlineAlert(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { children: children, variant: variant = 'neutral', autoFocus: autoFocus, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let slots = {
        heading: {
            UNSAFE_className: (0, ($parcel$interopDefault($6bfd7569368bc154$exports)))['spectrum-InLineAlert-heading']
        },
        content: {
            UNSAFE_className: (0, ($parcel$interopDefault($6bfd7569368bc154$exports)))['spectrum-InLineAlert-content']
        }
    };
    let stringFormatter = (0, $4izoM$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($848c7665a935e829$exports))), '@react-spectrum/inlinealert');
    let Icon = null;
    let iconAlt = '';
    if (variant in $ac5a0c58210bd748$var$ICONS) {
        Icon = $ac5a0c58210bd748$var$ICONS[variant];
        iconAlt = stringFormatter.format(variant);
    }
    let autoFocusRef = (0, $4izoM$react.useRef)(props.autoFocus);
    (0, $4izoM$react.useEffect)(()=>{
        if (autoFocusRef.current && domRef.current) domRef.current.focus();
        autoFocusRef.current = false;
    }, [
        domRef
    ]);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($4izoM$react))).createElement((0, $4izoM$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, ($parcel$interopDefault($6bfd7569368bc154$exports)))['focus-ring']
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4izoM$react))).createElement("div", {
        ...(0, $4izoM$reactariafilterDOMProps.filterDOMProps)(props),
        ...styleProps,
        ref: domRef,
        tabIndex: autoFocus ? -1 : undefined,
        autoFocus: autoFocus,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($6bfd7569368bc154$exports))), 'spectrum-InLineAlert', `spectrum-InLineAlert--${variant}`, styleProps.className),
        role: "alert"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4izoM$react))).createElement((0, $d6479700d21b596b$exports.Grid), {
        UNSAFE_className: (0, ($parcel$interopDefault($6bfd7569368bc154$exports)))['spectrum-InLineAlert-grid']
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4izoM$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: slots
    }, Icon && /*#__PURE__*/ (0, ($parcel$interopDefault($4izoM$react))).createElement(Icon, {
        UNSAFE_className: (0, ($parcel$interopDefault($6bfd7569368bc154$exports)))['spectrum-InLineAlert-icon'],
        "aria-label": iconAlt
    }), children))));
});


//# sourceMappingURL=InlineAlert.cjs.map
