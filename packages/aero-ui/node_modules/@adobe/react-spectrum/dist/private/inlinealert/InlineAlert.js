import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Grid as $727c1a1d9e8b8d73$export$ef2184bd89960b14} from "../layout/Grid.js";
import $6HPtK$intlStringsjs from "./intlStrings.js";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686} from "../utils/Slots.js";
import "../inlinealert_vars.css";
import $6HPtK$inlinealert_vars_cssmjs from "../inlinealert_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $6HPtK$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import {filterDOMProps as $6HPtK$filterDOMProps} from "react-aria/filterDOMProps";
import {FocusRing as $6HPtK$FocusRing} from "react-aria/FocusRing";
import $6HPtK$spectrumiconsuiInfoMedium from "@spectrum-icons/ui/InfoMedium";
import $6HPtK$react, {useRef as $6HPtK$useRef, useEffect as $6HPtK$useEffect} from "react";
import $6HPtK$spectrumiconsuiSuccessMedium from "@spectrum-icons/ui/SuccessMedium";
import {useLocalizedStringFormatter as $6HPtK$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
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














let $c59531e91448aebd$var$ICONS = {
    info: (0, $6HPtK$spectrumiconsuiInfoMedium),
    positive: (0, $6HPtK$spectrumiconsuiSuccessMedium),
    notice: (0, $6HPtK$spectrumiconsuiAlertMedium),
    negative: (0, $6HPtK$spectrumiconsuiAlertMedium)
};
const $c59531e91448aebd$export$a3b2c96db9b0eb71 = /*#__PURE__*/ (0, $6HPtK$react).forwardRef(function InlineAlert(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { children: children, variant: variant = 'neutral', autoFocus: autoFocus, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let slots = {
        heading: {
            UNSAFE_className: (0, ($parcel$interopDefault($6HPtK$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-heading']
        },
        content: {
            UNSAFE_className: (0, ($parcel$interopDefault($6HPtK$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-content']
        }
    };
    let stringFormatter = (0, $6HPtK$useLocalizedStringFormatter)((0, ($parcel$interopDefault($6HPtK$intlStringsjs))), '@react-spectrum/inlinealert');
    let Icon = null;
    let iconAlt = '';
    if (variant in $c59531e91448aebd$var$ICONS) {
        Icon = $c59531e91448aebd$var$ICONS[variant];
        iconAlt = stringFormatter.format(variant);
    }
    let autoFocusRef = (0, $6HPtK$useRef)(props.autoFocus);
    (0, $6HPtK$useEffect)(()=>{
        if (autoFocusRef.current && domRef.current) domRef.current.focus();
        autoFocusRef.current = false;
    }, [
        domRef
    ]);
    return /*#__PURE__*/ (0, $6HPtK$react).createElement((0, $6HPtK$FocusRing), {
        focusRingClass: (0, ($parcel$interopDefault($6HPtK$inlinealert_vars_cssmjs)))['focus-ring']
    }, /*#__PURE__*/ (0, $6HPtK$react).createElement("div", {
        ...(0, $6HPtK$filterDOMProps)(props),
        ...styleProps,
        ref: domRef,
        tabIndex: autoFocus ? -1 : undefined,
        autoFocus: autoFocus,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($6HPtK$inlinealert_vars_cssmjs))), 'spectrum-InLineAlert', `spectrum-InLineAlert--${variant}`, styleProps.className),
        role: "alert"
    }, /*#__PURE__*/ (0, $6HPtK$react).createElement((0, $727c1a1d9e8b8d73$export$ef2184bd89960b14), {
        UNSAFE_className: (0, ($parcel$interopDefault($6HPtK$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-grid']
    }, /*#__PURE__*/ (0, $6HPtK$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: slots
    }, Icon && /*#__PURE__*/ (0, $6HPtK$react).createElement(Icon, {
        UNSAFE_className: (0, ($parcel$interopDefault($6HPtK$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-icon'],
        "aria-label": iconAlt
    }), children))));
});


export {$c59531e91448aebd$export$a3b2c96db9b0eb71 as InlineAlert};
//# sourceMappingURL=InlineAlert.js.map
