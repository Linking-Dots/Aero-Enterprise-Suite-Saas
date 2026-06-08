var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../accordion_vars.css");
var $87b3ac9a331dbb82$exports = require("../accordion_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $5CK9N$reactariacomponentsButton = require("react-aria-components/Button");
var $5CK9N$spectrumiconsuiChevronLeftMedium = require("@spectrum-icons/ui/ChevronLeftMedium");
var $5CK9N$spectrumiconsuiChevronRightMedium = require("@spectrum-icons/ui/ChevronRightMedium");
var $5CK9N$reactariacomponentsDisclosureGroup = require("react-aria-components/DisclosureGroup");
var $5CK9N$reactariacomponentsHeading = require("react-aria-components/Heading");
var $5CK9N$react = require("react");
var $5CK9N$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Accordion", function () { return $d83dfd0b25ecb552$export$a766cd26d0d69044; });
$parcel$export(module.exports, "Disclosure", function () { return $d83dfd0b25ecb552$export$74a362b31437ec83; });
$parcel$export(module.exports, "DisclosurePanel", function () { return $d83dfd0b25ecb552$export$feabaa331e1d464c; });
$parcel$export(module.exports, "DisclosureTitle", function () { return $d83dfd0b25ecb552$export$7843c6a5b3e340a2; });
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











const $d83dfd0b25ecb552$var$InternalAccordionContext = /*#__PURE__*/ (0, $5CK9N$react.createContext)(null);
const $d83dfd0b25ecb552$export$a766cd26d0d69044 = /*#__PURE__*/ (0, $5CK9N$react.forwardRef)(function Accordion(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement($d83dfd0b25ecb552$var$InternalAccordionContext.Provider, {
        value: {
            isQuiet: props.isQuiet || false
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, $5CK9N$reactariacomponentsDisclosureGroup.DisclosureGroup), {
        ...props,
        ...styleProps,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion', styleProps.className)
    }, props.children));
});
const $d83dfd0b25ecb552$export$74a362b31437ec83 = /*#__PURE__*/ (0, $5CK9N$react.forwardRef)(function Disclosure(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let accordionContext = (0, ($parcel$interopDefault($5CK9N$react))).useContext($d83dfd0b25ecb552$var$InternalAccordionContext);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, $5CK9N$reactariacomponentsDisclosureGroup.Disclosure), {
        ...props,
        ...styleProps,
        ref: domRef,
        className: ({ isExpanded: isExpanded, isDisabled: isDisabled })=>(0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion-item', {
                'spectrum-Accordion-item--quiet': accordionContext?.isQuiet ?? props.isQuiet,
                'is-expanded': isExpanded,
                'is-disabled': isDisabled,
                'in-accordion': accordionContext != null
            }, styleProps.className)
    }, props.children);
});
const $d83dfd0b25ecb552$export$feabaa331e1d464c = /*#__PURE__*/ (0, $5CK9N$react.forwardRef)(function DisclosurePanel(props, ref) {
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, $5CK9N$reactariacomponentsDisclosureGroup.DisclosurePanel), {
        ref: domRef,
        ...styleProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion-itemContent', styleProps.className),
        ...props
    }, props.children);
});
const $d83dfd0b25ecb552$export$7843c6a5b3e340a2 = /*#__PURE__*/ (0, $5CK9N$react.forwardRef)(function DisclosureTitle(props, ref) {
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let { level: level = 3 } = props;
    let { direction: direction } = (0, $5CK9N$reactariaI18nProvider.useLocale)();
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, $5CK9N$reactariacomponentsHeading.Heading), {
        ref: domRef,
        level: level,
        ...styleProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion-itemHeading', styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, $5CK9N$reactariacomponentsButton.Button), {
        slot: "trigger",
        className: ({ isHovered: isHovered, isFocusVisible: isFocusVisible, isPressed: isPressed })=>(0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion-itemHeader', {
                'is-hovered': isHovered,
                'is-pressed': isPressed,
                'focus-ring': isFocusVisible
            })
    }, direction === 'ltr' ? /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, ($parcel$interopDefault($5CK9N$spectrumiconsuiChevronRightMedium))), {
        "aria-hidden": "true",
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion-itemIndicator')
    }) : /*#__PURE__*/ (0, ($parcel$interopDefault($5CK9N$react))).createElement((0, ($parcel$interopDefault($5CK9N$spectrumiconsuiChevronLeftMedium))), {
        "aria-hidden": "true",
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($87b3ac9a331dbb82$exports))), 'spectrum-Accordion-itemIndicator')
    }), props.children));
});


//# sourceMappingURL=Accordion.cjs.map
