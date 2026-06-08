import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ListBoxContext as $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d} from "./ListBoxContext.mjs";
import "../menu_vars.css";
import $152TB$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {layoutInfoToStyle as $152TB$layoutInfoToStyle} from "react-aria/private/virtualizer/VirtualizerItem";
import $152TB$react, {useRef as $152TB$useRef, useContext as $152TB$useContext, Fragment as $152TB$Fragment} from "react";
import {useListBoxSection as $152TB$useListBoxSection} from "react-aria/useListBox";
import {useLocale as $152TB$useLocale} from "react-aria/I18nProvider";
import {useVirtualizerItem as $152TB$useVirtualizerItem} from "react-aria/private/virtualizer/useVirtualizerItem";


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







function $0d295233586bb40d$export$dca12b0bb56e4fc(props) {
    let { children: children, layoutInfo: layoutInfo, headerLayoutInfo: headerLayoutInfo, virtualizer: virtualizer, item: item } = props;
    let { headingProps: headingProps, groupProps: groupProps } = (0, $152TB$useListBoxSection)({
        heading: item.rendered,
        'aria-label': item['aria-label']
    });
    let headerRef = (0, $152TB$useRef)(null);
    (0, $152TB$useVirtualizerItem)({
        layoutInfo: headerLayoutInfo,
        virtualizer: virtualizer,
        ref: headerRef
    });
    let { direction: direction } = (0, $152TB$useLocale)();
    let { state: state } = (0, $152TB$useContext)((0, $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d));
    return /*#__PURE__*/ (0, $152TB$react).createElement((0, $152TB$Fragment), null, headerLayoutInfo && /*#__PURE__*/ (0, $152TB$react).createElement("div", {
        role: "presentation",
        ref: headerRef,
        style: (0, $152TB$layoutInfoToStyle)(headerLayoutInfo, direction)
    }, item.key !== state.collection.getFirstKey() && /*#__PURE__*/ (0, $152TB$react).createElement("div", {
        role: "presentation",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($152TB$menu_vars_cssmjs))), 'spectrum-Menu-divider')
    }), item.rendered && /*#__PURE__*/ (0, $152TB$react).createElement("div", {
        ...headingProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($152TB$menu_vars_cssmjs))), 'spectrum-Menu-sectionHeading')
    }, item.rendered)), /*#__PURE__*/ (0, $152TB$react).createElement("div", {
        ...groupProps,
        style: (0, $152TB$layoutInfoToStyle)(layoutInfo, direction),
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($152TB$menu_vars_cssmjs))), 'spectrum-Menu')
    }, children));
}


export {$0d295233586bb40d$export$dca12b0bb56e4fc as ListBoxSection};
//# sourceMappingURL=ListBoxSection.mjs.map
