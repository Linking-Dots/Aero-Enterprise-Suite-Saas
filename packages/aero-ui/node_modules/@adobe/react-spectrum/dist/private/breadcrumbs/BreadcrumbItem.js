import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../breadcrumb_vars.css";
import $7XLn0$breadcrumb_vars_cssmjs from "../breadcrumb_vars_css.mjs";
import {useBreadcrumbItem as $7XLn0$useBreadcrumbItem} from "react-aria/useBreadcrumbs";
import $7XLn0$spectrumiconsuiChevronRightSmall from "@spectrum-icons/ui/ChevronRightSmall";
import {FocusRing as $7XLn0$FocusRing} from "react-aria/FocusRing";
import {mergeProps as $7XLn0$mergeProps} from "react-aria/mergeProps";
import $7XLn0$react, {useRef as $7XLn0$useRef, Fragment as $7XLn0$Fragment} from "react";
import {useHover as $7XLn0$useHover} from "react-aria/useHover";
import {useLocale as $7XLn0$useLocale} from "react-aria/I18nProvider";


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








function $737db8b958dde4c0$export$c13f210c706eb549(props) {
    let { children: children, isCurrent: isCurrent, isDisabled: isDisabled, isMenu: isMenu } = props;
    let { direction: direction } = (0, $7XLn0$useLocale)();
    let ref = (0, $7XLn0$useRef)(null);
    let ElementType = props.href ? 'a' : 'span';
    let { itemProps: itemProps } = (0, $7XLn0$useBreadcrumbItem)({
        ...props,
        elementType: ElementType
    }, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $7XLn0$useHover)(props);
    // If this item contains a menu button, then it shouldn't be a link.
    if (isMenu) itemProps = {};
    return /*#__PURE__*/ (0, $7XLn0$react).createElement((0, $7XLn0$Fragment), null, /*#__PURE__*/ (0, $7XLn0$react).createElement((0, $7XLn0$FocusRing), {
        focusRingClass: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7XLn0$breadcrumb_vars_cssmjs))), 'focus-ring')
    }, /*#__PURE__*/ (0, $7XLn0$react).createElement(ElementType, {
        ...(0, $7XLn0$mergeProps)(itemProps, hoverProps),
        ref: ref,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7XLn0$breadcrumb_vars_cssmjs))), {
            'spectrum-Breadcrumbs-itemLink': !isMenu,
            'is-disabled': !isCurrent && isDisabled,
            'is-hovered': isHovered
        })
    }, children)), /*#__PURE__*/ (0, $7XLn0$react).createElement((0, $7XLn0$spectrumiconsuiChevronRightSmall), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7XLn0$breadcrumb_vars_cssmjs))), 'spectrum-Breadcrumbs-itemSeparator', {
            'is-reversed': direction === 'rtl'
        })
    }));
}


export {$737db8b958dde4c0$export$c13f210c706eb549 as BreadcrumbItem};
//# sourceMappingURL=BreadcrumbItem.js.map
