import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Grid as $572f9fec526c2697$export$ef2184bd89960b14} from "../layout/Grid.mjs";
import $it020$intlStringsmjs from "./intlStrings.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686} from "../utils/Slots.mjs";
import "../inlinealert_vars.css";
import $it020$inlinealert_vars_cssmjs from "../inlinealert_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $it020$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import {filterDOMProps as $it020$filterDOMProps} from "react-aria/filterDOMProps";
import {FocusRing as $it020$FocusRing} from "react-aria/FocusRing";
import $it020$spectrumiconsuiInfoMedium from "@spectrum-icons/ui/InfoMedium";
import $it020$react, {useRef as $it020$useRef, useEffect as $it020$useEffect} from "react";
import $it020$spectrumiconsuiSuccessMedium from "@spectrum-icons/ui/SuccessMedium";
import {useLocalizedStringFormatter as $it020$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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














let $30b913e831f873cb$var$ICONS = {
    info: (0, $it020$spectrumiconsuiInfoMedium),
    positive: (0, $it020$spectrumiconsuiSuccessMedium),
    notice: (0, $it020$spectrumiconsuiAlertMedium),
    negative: (0, $it020$spectrumiconsuiAlertMedium)
};
const $30b913e831f873cb$export$a3b2c96db9b0eb71 = /*#__PURE__*/ (0, $it020$react).forwardRef(function InlineAlert(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { children: children, variant: variant = 'neutral', autoFocus: autoFocus, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let slots = {
        heading: {
            UNSAFE_className: (0, ($parcel$interopDefault($it020$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-heading']
        },
        content: {
            UNSAFE_className: (0, ($parcel$interopDefault($it020$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-content']
        }
    };
    let stringFormatter = (0, $it020$useLocalizedStringFormatter)((0, ($parcel$interopDefault($it020$intlStringsmjs))), '@react-spectrum/inlinealert');
    let Icon = null;
    let iconAlt = '';
    if (variant in $30b913e831f873cb$var$ICONS) {
        Icon = $30b913e831f873cb$var$ICONS[variant];
        iconAlt = stringFormatter.format(variant);
    }
    let autoFocusRef = (0, $it020$useRef)(props.autoFocus);
    (0, $it020$useEffect)(()=>{
        if (autoFocusRef.current && domRef.current) domRef.current.focus();
        autoFocusRef.current = false;
    }, [
        domRef
    ]);
    return /*#__PURE__*/ (0, $it020$react).createElement((0, $it020$FocusRing), {
        focusRingClass: (0, ($parcel$interopDefault($it020$inlinealert_vars_cssmjs)))['focus-ring']
    }, /*#__PURE__*/ (0, $it020$react).createElement("div", {
        ...(0, $it020$filterDOMProps)(props),
        ...styleProps,
        ref: domRef,
        tabIndex: autoFocus ? -1 : undefined,
        autoFocus: autoFocus,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($it020$inlinealert_vars_cssmjs))), 'spectrum-InLineAlert', `spectrum-InLineAlert--${variant}`, styleProps.className),
        role: "alert"
    }, /*#__PURE__*/ (0, $it020$react).createElement((0, $572f9fec526c2697$export$ef2184bd89960b14), {
        UNSAFE_className: (0, ($parcel$interopDefault($it020$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-grid']
    }, /*#__PURE__*/ (0, $it020$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: slots
    }, Icon && /*#__PURE__*/ (0, $it020$react).createElement(Icon, {
        UNSAFE_className: (0, ($parcel$interopDefault($it020$inlinealert_vars_cssmjs)))['spectrum-InLineAlert-icon'],
        "aria-label": iconAlt
    }), children))));
});


export {$30b913e831f873cb$export$a3b2c96db9b0eb71 as InlineAlert};
//# sourceMappingURL=InlineAlert.mjs.map
