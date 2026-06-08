var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../breadcrumb_vars.css");
var $c876acff1ed460c9$exports = require("../breadcrumb_vars_css.cjs");
var $4Gxve$reactariauseBreadcrumbs = require("react-aria/useBreadcrumbs");
var $4Gxve$spectrumiconsuiChevronRightSmall = require("@spectrum-icons/ui/ChevronRightSmall");
var $4Gxve$reactariaFocusRing = require("react-aria/FocusRing");
var $4Gxve$reactariamergeProps = require("react-aria/mergeProps");
var $4Gxve$react = require("react");
var $4Gxve$reactariauseHover = require("react-aria/useHover");
var $4Gxve$reactariaI18nProvider = require("react-aria/I18nProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "BreadcrumbItem", function () { return $113504779a40c9d6$export$c13f210c706eb549; });
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








function $113504779a40c9d6$export$c13f210c706eb549(props) {
    let { children: children, isCurrent: isCurrent, isDisabled: isDisabled, isMenu: isMenu } = props;
    let { direction: direction } = (0, $4Gxve$reactariaI18nProvider.useLocale)();
    let ref = (0, $4Gxve$react.useRef)(null);
    let ElementType = props.href ? 'a' : 'span';
    let { itemProps: itemProps } = (0, $4Gxve$reactariauseBreadcrumbs.useBreadcrumbItem)({
        ...props,
        elementType: ElementType
    }, ref);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $4Gxve$reactariauseHover.useHover)(props);
    // If this item contains a menu button, then it shouldn't be a link.
    if (isMenu) itemProps = {};
    return /*#__PURE__*/ (0, ($parcel$interopDefault($4Gxve$react))).createElement((0, $4Gxve$react.Fragment), null, /*#__PURE__*/ (0, ($parcel$interopDefault($4Gxve$react))).createElement((0, $4Gxve$reactariaFocusRing.FocusRing), {
        focusRingClass: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c876acff1ed460c9$exports))), 'focus-ring')
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($4Gxve$react))).createElement(ElementType, {
        ...(0, $4Gxve$reactariamergeProps.mergeProps)(itemProps, hoverProps),
        ref: ref,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c876acff1ed460c9$exports))), {
            'spectrum-Breadcrumbs-itemLink': !isMenu,
            'is-disabled': !isCurrent && isDisabled,
            'is-hovered': isHovered
        })
    }, children)), /*#__PURE__*/ (0, ($parcel$interopDefault($4Gxve$react))).createElement((0, ($parcel$interopDefault($4Gxve$spectrumiconsuiChevronRightSmall))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($c876acff1ed460c9$exports))), 'spectrum-Breadcrumbs-itemSeparator', {
            'is-reversed': direction === 'rtl'
        })
    }));
}


//# sourceMappingURL=BreadcrumbItem.cjs.map
