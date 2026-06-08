import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../breadcrumb_vars.css";
import $fDGVV$breadcrumb_vars_cssmjs from "../breadcrumb_vars_css.mjs";
import {useBreadcrumbItem as $fDGVV$useBreadcrumbItem} from "react-aria/useBreadcrumbs";
import $fDGVV$spectrumiconsuiChevronRightSmall from "@spectrum-icons/ui/ChevronRightSmall";
import {FocusRing as $fDGVV$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $fDGVV$mergeProps} from "react-aria/mergeProps";
import $fDGVV$react, {useRef as $fDGVV$useRef, Fragment as $fDGVV$Fragment} from "react";
import {useHover as $fDGVV$useHover} from "react-aria/useHover";
import {useLocale as $fDGVV$useLocale} from "react-aria/I18nProvider";


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








function $340e2b286c56680c$export$c13f210c706eb549(props) {
    let { children: children, isCurrent: isCurrent, isDisabled: isDisabled, isMenu: isMenu } = props;
    let { direction: direction } = (0, $fDGVV$useLocale)();
    let ref = (0, $fDGVV$useRef)(null);
    let ElementType = props.href ? 'a' : 'span';
    let { itemProps: itemProps } = (0, $fDGVV$useBreadcrumbItem)({
        ...props,
        elementType: ElementType
    }, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $fDGVV$useHover)(props);
    // If this item contains a menu button, then it shouldn't be a link.
    if (isMenu) itemProps = {};
    return /*#__PURE__*/ (0, $fDGVV$react).createElement((0, $fDGVV$Fragment), null, /*#__PURE__*/ (0, $fDGVV$react).createElement((0, $fDGVV$FocusRing), {
        focusRingClass: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fDGVV$breadcrumb_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $fDGVV$react).createElement(ElementType, {
        ...(0, $fDGVV$mergeProps)(itemProps, hoverProps),
        ref: ref,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fDGVV$breadcrumb_vars_cssmjs))), {
            'spectrum-Breadcrumbs-itemLink': !isMenu,
            'is-disabled': !isCurrent && isDisabled,
            'is-hovered': isHovered
        })
    }, children)), /*#__PURE__*/ (0, $fDGVV$react).createElement((0, $fDGVV$spectrumiconsuiChevronRightSmall), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($fDGVV$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs-itemSeparator', {
            'is-reversed': direction === 'rtl'
        })
    }));
}


export {$340e2b286c56680c$export$c13f210c706eb549 as BreadcrumbItem};
//# sourceMappingURL=BreadcrumbItem.mjs.map
