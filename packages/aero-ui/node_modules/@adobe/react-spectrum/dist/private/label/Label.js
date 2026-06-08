import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import $ecuSN$intlStringsjs from "./intlStrings.js";
import "../fieldlabel_vars.css";
import $ecuSN$fieldlabel_vars_cssmjs from "../fieldlabel_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import $ecuSN$spectrumiconsuiAsterisk from "@spectrum-icons/ui/Asterisk";
import {filterDOMProps as $ecuSN$filterDOMProps} from "react-aria/filterDOMProps";
import $ecuSN$react from "react";
import {useLocalizedStringFormatter as $ecuSN$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
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









const $323da7a023c7a11f$export$b04be29aa201d4f5 = /*#__PURE__*/ (0, $ecuSN$react).forwardRef(function Label(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { children: children, labelPosition: labelPosition = 'top', labelAlign: labelAlign = labelPosition === 'side' ? 'start' : null, isRequired: isRequired, necessityIndicator: necessityIndicator = isRequired != null ? 'icon' : null, includeNecessityIndicatorInAccessibilityName: includeNecessityIndicatorInAccessibilityName = false, htmlFor: htmlFor, for: labelFor, elementType: ElementType = 'label', onClick: onClick, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let stringFormatter = (0, $ecuSN$useLocalizedStringFormatter)((0, ($parcel$interopDefault($ecuSN$intlStringsjs))), '@react-spectrum/label');
    let necessityLabel = isRequired ? stringFormatter.format('(required)') : stringFormatter.format('(optional)');
    let icon = /*#__PURE__*/ (0, $ecuSN$react).createElement((0, $ecuSN$spectrumiconsuiAsterisk), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ecuSN$fieldlabel_vars_cssmjs))), 'spectrum-FieldLabel-requiredIcon'),
        "aria-label": includeNecessityIndicatorInAccessibilityName ? stringFormatter.format('(required)') : undefined
    });
    let labelClassNames = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($ecuSN$fieldlabel_vars_cssmjs))), 'spectrum-FieldLabel', {
        'spectrum-FieldLabel--positionSide': labelPosition === 'side',
        'spectrum-FieldLabel--alignEnd': labelAlign === 'end'
    }, styleProps.className);
    return /*#__PURE__*/ (0, $ecuSN$react).createElement(ElementType, {
        ...(0, $ecuSN$filterDOMProps)(otherProps),
        ...styleProps,
        onClick: onClick,
        ref: domRef,
        className: labelClassNames,
        htmlFor: ElementType === 'label' ? labelFor || htmlFor : undefined
    }, children, (necessityIndicator === 'label' || necessityIndicator === 'icon' && isRequired) && ' \u200b', necessityIndicator === 'label' && /*#__PURE__*/ (0, $ecuSN$react).createElement("span", {
        "aria-hidden": !includeNecessityIndicatorInAccessibilityName ? isRequired : undefined
    }, necessityLabel), necessityIndicator === 'icon' && isRequired && icon);
});


export {$323da7a023c7a11f$export$b04be29aa201d4f5 as Label};
//# sourceMappingURL=Label.js.map
