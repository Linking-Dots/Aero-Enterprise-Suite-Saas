var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b02f16a34f83c86b$exports = require("./ListBoxContext.cjs");
require("../menu_vars.css");
var $35d34152ff885d5c$exports = require("../menu_vars_css.cjs");
var $edpDN$reactariaprivatevirtualizerVirtualizerItem = require("react-aria/private/virtualizer/VirtualizerItem");
var $edpDN$react = require("react");
var $edpDN$reactariauseListBox = require("react-aria/useListBox");
var $edpDN$reactariaI18nProvider = require("react-aria/I18nProvider");
var $edpDN$reactariaprivatevirtualizeruseVirtualizerItem = require("react-aria/private/virtualizer/useVirtualizerItem");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ListBoxSection", function () { return $07d691d60ee24aa0$export$dca12b0bb56e4fc; });
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







function $07d691d60ee24aa0$export$dca12b0bb56e4fc(props) {
    let { children: children, layoutInfo: layoutInfo, headerLayoutInfo: headerLayoutInfo, virtualizer: virtualizer, item: item } = props;
    let { headingProps: headingProps, groupProps: groupProps } = (0, $edpDN$reactariauseListBox.useListBoxSection)({
        heading: item.rendered,
        'aria-label': item['aria-label']
    });
    let headerRef = (0, $edpDN$react.useRef)(null);
    (0, $edpDN$reactariaprivatevirtualizeruseVirtualizerItem.useVirtualizerItem)({
        layoutInfo: headerLayoutInfo,
        virtualizer: virtualizer,
        ref: headerRef
    });
    let { direction: direction } = (0, $edpDN$reactariaI18nProvider.useLocale)();
    let { state: state } = (0, $edpDN$react.useContext)((0, $b02f16a34f83c86b$exports.ListBoxContext));
    return /*#__PURE__*/ (0, ($parcel$interopDefault($edpDN$react))).createElement((0, $edpDN$react.Fragment), null, headerLayoutInfo && /*#__PURE__*/ (0, ($parcel$interopDefault($edpDN$react))).createElement("div", {
        role: "presentation",
        ref: headerRef,
        style: (0, $edpDN$reactariaprivatevirtualizerVirtualizerItem.layoutInfoToStyle)(headerLayoutInfo, direction)
    }, item.key !== state.collection.getFirstKey() && /*#__PURE__*/ (0, ($parcel$interopDefault($edpDN$react))).createElement("div", {
        role: "presentation",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-divider')
    }), item.rendered && /*#__PURE__*/ (0, ($parcel$interopDefault($edpDN$react))).createElement("div", {
        ...headingProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu-sectionHeading')
    }, item.rendered)), /*#__PURE__*/ (0, ($parcel$interopDefault($edpDN$react))).createElement("div", {
        ...groupProps,
        style: (0, $edpDN$reactariaprivatevirtualizerVirtualizerItem.layoutInfoToStyle)(layoutInfo, direction),
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu')
    }, children));
}


//# sourceMappingURL=ListBoxSection.cjs.map
