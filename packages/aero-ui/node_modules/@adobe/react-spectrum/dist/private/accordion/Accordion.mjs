import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../accordion_vars.css";
import $2CoFZ$accordion_vars_cssmjs from "../accordion_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {Button as $2CoFZ$Button} from "react-aria-components/Button";
import $2CoFZ$spectrumiconsuiChevronLeftMedium from "@spectrum-icons/ui/ChevronLeftMedium";
import $2CoFZ$spectrumiconsuiChevronRightMedium from "@spectrum-icons/ui/ChevronRightMedium";
import {DisclosureGroup as $2CoFZ$DisclosureGroup, Disclosure as $2CoFZ$Disclosure, DisclosurePanel as $2CoFZ$DisclosurePanel} from "react-aria-components/DisclosureGroup";
import {Heading as $2CoFZ$Heading} from "react-aria-components/Heading";
import $2CoFZ$react, {createContext as $2CoFZ$createContext, forwardRef as $2CoFZ$forwardRef} from "react";
import {useLocale as $2CoFZ$useLocale} from "react-aria/I18nProvider";


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











const $3a7c660fa4cc139b$var$InternalAccordionContext = /*#__PURE__*/ (0, $2CoFZ$createContext)(null);
const $3a7c660fa4cc139b$export$a766cd26d0d69044 = /*#__PURE__*/ (0, $2CoFZ$forwardRef)(function Accordion(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $2CoFZ$react).createElement($3a7c660fa4cc139b$var$InternalAccordionContext.Provider, {
        value: {
            isQuiet: props.isQuiet || false
        }
    }, /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$DisclosureGroup), {
        ...props,
        ...styleProps,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion', styleProps.className)
    }, props.children));
});
const $3a7c660fa4cc139b$export$74a362b31437ec83 = /*#__PURE__*/ (0, $2CoFZ$forwardRef)(function Disclosure(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let accordionContext = (0, $2CoFZ$react).useContext($3a7c660fa4cc139b$var$InternalAccordionContext);
    return /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$Disclosure), {
        ...props,
        ...styleProps,
        ref: domRef,
        className: ({ isExpanded: isExpanded, isDisabled: isDisabled })=>(0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion-item', {
                'spectrum-Accordion-item--quiet': accordionContext?.isQuiet ?? props.isQuiet,
                'is-expanded': isExpanded,
                'is-disabled': isDisabled,
                'in-accordion': accordionContext != null
            }, styleProps.className)
    }, props.children);
});
const $3a7c660fa4cc139b$export$feabaa331e1d464c = /*#__PURE__*/ (0, $2CoFZ$forwardRef)(function DisclosurePanel(props, ref) {
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$DisclosurePanel), {
        ref: domRef,
        ...styleProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion-itemContent', styleProps.className),
        ...props
    }, props.children);
});
const $3a7c660fa4cc139b$export$7843c6a5b3e340a2 = /*#__PURE__*/ (0, $2CoFZ$forwardRef)(function DisclosureTitle(props, ref) {
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let { level: level = 3 } = props;
    let { direction: direction } = (0, $2CoFZ$useLocale)();
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$Heading), {
        ref: domRef,
        level: level,
        ...styleProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion-itemHeading', styleProps.className)
    }, /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$Button), {
        slot: "trigger",
        className: ({ isHovered: isHovered, isFocusVisible: isFocusVisible, isPressed: isPressed })=>(0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion-itemHeader', {
                'is-hovered': isHovered,
                'is-pressed': isPressed,
                'focus-ring': isFocusVisible
            })
    }, direction === 'ltr' ? /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$spectrumiconsuiChevronRightMedium), {
        "aria-hidden": "true",
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion-itemIndicator')
    }) : /*#__PURE__*/ (0, $2CoFZ$react).createElement((0, $2CoFZ$spectrumiconsuiChevronLeftMedium), {
        "aria-hidden": "true",
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($2CoFZ$accordion_vars_cssmjs))), 'spectrum-Accordion-itemIndicator')
    }), props.children));
});


export {$3a7c660fa4cc139b$export$a766cd26d0d69044 as Accordion, $3a7c660fa4cc139b$export$74a362b31437ec83 as Disclosure, $3a7c660fa4cc139b$export$feabaa331e1d464c as DisclosurePanel, $3a7c660fa4cc139b$export$7843c6a5b3e340a2 as DisclosureTitle};
//# sourceMappingURL=Accordion.mjs.map
