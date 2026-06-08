import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import $9jVEu$intlStringsmjs from "./intlStrings.mjs";
import "../fieldlabel_vars.css";
import $9jVEu$fieldlabel_vars_cssmjs from "../fieldlabel_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import $9jVEu$spectrumiconsuiAsterisk from "@spectrum-icons/ui/Asterisk";
import {filterDOMProps as $9jVEu$filterDOMProps} from "react-aria/filterDOMProps";
import $9jVEu$react from "react";
import {useLocalizedStringFormatter as $9jVEu$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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









const $f6f5235bab1fa21e$export$b04be29aa201d4f5 = /*#__PURE__*/ (0, $9jVEu$react).forwardRef(function Label(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { children: children, labelPosition: labelPosition = 'top', labelAlign: labelAlign = labelPosition === 'side' ? 'start' : null, isRequired: isRequired, necessityIndicator: necessityIndicator = isRequired != null ? 'icon' : null, includeNecessityIndicatorInAccessibilityName: includeNecessityIndicatorInAccessibilityName = false, htmlFor: htmlFor, for: labelFor, elementType: ElementType = 'label', onClick: onClick, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let stringFormatter = (0, $9jVEu$useLocalizedStringFormatter)((0, ($parcel$interopDefault($9jVEu$intlStringsmjs))), '@react-spectrum/label');
    let necessityLabel = isRequired ? stringFormatter.format('(required)') : stringFormatter.format('(optional)');
    let icon = /*#__PURE__*/ (0, $9jVEu$react).createElement((0, $9jVEu$spectrumiconsuiAsterisk), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9jVEu$fieldlabel_vars_cssmjs))), 'spectrum-FieldLabel-requiredIcon'),
        "aria-label": includeNecessityIndicatorInAccessibilityName ? stringFormatter.format('(required)') : undefined
    });
    let labelClassNames = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9jVEu$fieldlabel_vars_cssmjs))), 'spectrum-FieldLabel', {
        'spectrum-FieldLabel--positionSide': labelPosition === 'side',
        'spectrum-FieldLabel--alignEnd': labelAlign === 'end'
    }, styleProps.className);
    return /*#__PURE__*/ (0, $9jVEu$react).createElement(ElementType, {
        ...(0, $9jVEu$filterDOMProps)(otherProps),
        ...styleProps,
        onClick: onClick,
        ref: domRef,
        className: labelClassNames,
        htmlFor: ElementType === 'label' ? labelFor || htmlFor : undefined
    }, children, (necessityIndicator === 'label' || necessityIndicator === 'icon' && isRequired) && ' \u200b', necessityIndicator === 'label' && /*#__PURE__*/ (0, $9jVEu$react).createElement("span", {
        "aria-hidden": !includeNecessityIndicatorInAccessibilityName ? isRequired : undefined
    }, necessityLabel), necessityIndicator === 'icon' && isRequired && icon);
});


export {$f6f5235bab1fa21e$export$b04be29aa201d4f5 as Label};
//# sourceMappingURL=Label.mjs.map
