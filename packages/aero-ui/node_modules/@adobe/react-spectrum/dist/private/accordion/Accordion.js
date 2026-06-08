import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../accordion_vars.css";
import $eQ8Mf$accordion_vars_cssmjs from "../accordion_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {Button as $eQ8Mf$Button} from "react-aria-components/Button";
import $eQ8Mf$spectrumiconsuiChevronLeftMedium from "@spectrum-icons/ui/ChevronLeftMedium";
import $eQ8Mf$spectrumiconsuiChevronRightMedium from "@spectrum-icons/ui/ChevronRightMedium";
import {DisclosureGroup as $eQ8Mf$DisclosureGroup, Disclosure as $eQ8Mf$Disclosure, DisclosurePanel as $eQ8Mf$DisclosurePanel} from "react-aria-components/DisclosureGroup";
import {Heading as $eQ8Mf$Heading} from "react-aria-components/Heading";
import $eQ8Mf$react, {createContext as $eQ8Mf$createContext, forwardRef as $eQ8Mf$forwardRef} from "react";
import {useLocale as $eQ8Mf$useLocale} from "react-aria/I18nProvider";


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











const $d33033465c302d3d$var$InternalAccordionContext = /*#__PURE__*/ (0, $eQ8Mf$createContext)(null);
const $d33033465c302d3d$export$a766cd26d0d69044 = /*#__PURE__*/ (0, $eQ8Mf$forwardRef)(function Accordion(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $eQ8Mf$react).createElement($d33033465c302d3d$var$InternalAccordionContext.Provider, {
        value: {
            isQuiet: props.isQuiet || false
        }
    }, /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$DisclosureGroup), {
        ...props,
        ...styleProps,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion', styleProps.className)
    }, props.children));
});
const $d33033465c302d3d$export$74a362b31437ec83 = /*#__PURE__*/ (0, $eQ8Mf$forwardRef)(function Disclosure(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let accordionContext = (0, $eQ8Mf$react).useContext($d33033465c302d3d$var$InternalAccordionContext);
    return /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$Disclosure), {
        ...props,
        ...styleProps,
        ref: domRef,
        className: ({ isExpanded: isExpanded, isDisabled: isDisabled })=>{
            var _accordionContext_isQuiet;
            return (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion-item', {
                'spectrum-Accordion-item--quiet': (_accordionContext_isQuiet = accordionContext === null || accordionContext === void 0 ? void 0 : accordionContext.isQuiet) !== null && _accordionContext_isQuiet !== void 0 ? _accordionContext_isQuiet : props.isQuiet,
                'is-expanded': isExpanded,
                'is-disabled': isDisabled,
                'in-accordion': accordionContext != null
            }, styleProps.className);
        }
    }, props.children);
});
const $d33033465c302d3d$export$feabaa331e1d464c = /*#__PURE__*/ (0, $eQ8Mf$forwardRef)(function DisclosurePanel(props, ref) {
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$DisclosurePanel), {
        ref: domRef,
        ...styleProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion-itemContent', styleProps.className),
        ...props
    }, props.children);
});
const $d33033465c302d3d$export$7843c6a5b3e340a2 = /*#__PURE__*/ (0, $eQ8Mf$forwardRef)(function DisclosureTitle(props, ref) {
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let { level: level = 3 } = props;
    let { direction: direction } = (0, $eQ8Mf$useLocale)();
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    return /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$Heading), {
        ref: domRef,
        level: level,
        ...styleProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion-itemHeading', styleProps.className)
    }, /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$Button), {
        slot: "trigger",
        className: ({ isHovered: isHovered, isFocusVisible: isFocusVisible, isPressed: isPressed })=>(0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion-itemHeader', {
                'is-hovered': isHovered,
                'is-pressed': isPressed,
                'focus-ring': isFocusVisible
            })
    }, direction === 'ltr' ? /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$spectrumiconsuiChevronRightMedium), {
        "aria-hidden": "true",
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion-itemIndicator')
    }) : /*#__PURE__*/ (0, $eQ8Mf$react).createElement((0, $eQ8Mf$spectrumiconsuiChevronLeftMedium), {
        "aria-hidden": "true",
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eQ8Mf$accordion_vars_cssmjs))), 'spectrum-Accordion-itemIndicator')
    }), props.children));
});


export {$d33033465c302d3d$export$a766cd26d0d69044 as Accordion, $d33033465c302d3d$export$74a362b31437ec83 as Disclosure, $d33033465c302d3d$export$feabaa331e1d464c as DisclosurePanel, $d33033465c302d3d$export$7843c6a5b3e340a2 as DisclosureTitle};
//# sourceMappingURL=Accordion.js.map
