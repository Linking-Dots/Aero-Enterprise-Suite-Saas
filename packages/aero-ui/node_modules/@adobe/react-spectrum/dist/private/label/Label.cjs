var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $981ea3d6fd7e0376$exports = require("./intlStrings.cjs");
require("../fieldlabel_vars.css");
var $53185441bef09fa8$exports = require("../fieldlabel_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $83HoU$spectrumiconsuiAsterisk = require("@spectrum-icons/ui/Asterisk");
var $83HoU$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $83HoU$react = require("react");
var $83HoU$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Label", function () { return $b881bddc71fd043e$export$b04be29aa201d4f5; });
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









const $b881bddc71fd043e$export$b04be29aa201d4f5 = /*#__PURE__*/ (0, ($parcel$interopDefault($83HoU$react))).forwardRef(function Label(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { children: children, labelPosition: labelPosition = 'top', labelAlign: labelAlign = labelPosition === 'side' ? 'start' : null, isRequired: isRequired, necessityIndicator: necessityIndicator = isRequired != null ? 'icon' : null, includeNecessityIndicatorInAccessibilityName: includeNecessityIndicatorInAccessibilityName = false, htmlFor: htmlFor, for: labelFor, elementType: ElementType = 'label', onClick: onClick, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let stringFormatter = (0, $83HoU$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($981ea3d6fd7e0376$exports))), '@react-spectrum/label');
    let necessityLabel = isRequired ? stringFormatter.format('(required)') : stringFormatter.format('(optional)');
    let icon = /*#__PURE__*/ (0, ($parcel$interopDefault($83HoU$react))).createElement((0, ($parcel$interopDefault($83HoU$spectrumiconsuiAsterisk))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($53185441bef09fa8$exports))), 'spectrum-FieldLabel-requiredIcon'),
        "aria-label": includeNecessityIndicatorInAccessibilityName ? stringFormatter.format('(required)') : undefined
    });
    let labelClassNames = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($53185441bef09fa8$exports))), 'spectrum-FieldLabel', {
        'spectrum-FieldLabel--positionSide': labelPosition === 'side',
        'spectrum-FieldLabel--alignEnd': labelAlign === 'end'
    }, styleProps.className);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($83HoU$react))).createElement(ElementType, {
        ...(0, $83HoU$reactariafilterDOMProps.filterDOMProps)(otherProps),
        ...styleProps,
        onClick: onClick,
        ref: domRef,
        className: labelClassNames,
        htmlFor: ElementType === 'label' ? labelFor || htmlFor : undefined
    }, children, (necessityIndicator === 'label' || necessityIndicator === 'icon' && isRequired) && ' \u200b', necessityIndicator === 'label' && /*#__PURE__*/ (0, ($parcel$interopDefault($83HoU$react))).createElement("span", {
        "aria-hidden": !includeNecessityIndicatorInAccessibilityName ? isRequired : undefined
    }, necessityLabel), necessityIndicator === 'icon' && isRequired && icon);
});


//# sourceMappingURL=Label.cjs.map
