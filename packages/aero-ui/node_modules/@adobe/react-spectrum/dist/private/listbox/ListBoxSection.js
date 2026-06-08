import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ListBoxContext as $90de0e4b1949420b$export$7ff8f37d2d81a48d} from "./ListBoxContext.js";
import "../menu_vars.css";
import $cgdQC$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {layoutInfoToStyle as $cgdQC$layoutInfoToStyle} from "react-aria/private/virtualizer/VirtualizerItem";
import $cgdQC$react, {useRef as $cgdQC$useRef, useContext as $cgdQC$useContext, Fragment as $cgdQC$Fragment} from "react";
import {useListBoxSection as $cgdQC$useListBoxSection} from "react-aria/useListBox";
import {useLocale as $cgdQC$useLocale} from "react-aria/I18nProvider";
import {useVirtualizerItem as $cgdQC$useVirtualizerItem} from "react-aria/private/virtualizer/useVirtualizerItem";


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







function $35ee08a5b7105872$export$dca12b0bb56e4fc(props) {
    let { children: children, layoutInfo: layoutInfo, headerLayoutInfo: headerLayoutInfo, virtualizer: virtualizer, item: item } = props;
    let { headingProps: headingProps, groupProps: groupProps } = (0, $cgdQC$useListBoxSection)({
        heading: item.rendered,
        'aria-label': item['aria-label']
    });
    let headerRef = (0, $cgdQC$useRef)(null);
    (0, $cgdQC$useVirtualizerItem)({
        layoutInfo: headerLayoutInfo,
        virtualizer: virtualizer,
        ref: headerRef
    });
    let { direction: direction } = (0, $cgdQC$useLocale)();
    let { state: state } = (0, $cgdQC$useContext)((0, $90de0e4b1949420b$export$7ff8f37d2d81a48d));
    return /*#__PURE__*/ (0, $cgdQC$react).createElement((0, $cgdQC$Fragment), null, headerLayoutInfo && /*#__PURE__*/ (0, $cgdQC$react).createElement("div", {
        role: "presentation",
        ref: headerRef,
        style: (0, $cgdQC$layoutInfoToStyle)(headerLayoutInfo, direction)
    }, item.key !== state.collection.getFirstKey() && /*#__PURE__*/ (0, $cgdQC$react).createElement("div", {
        role: "presentation",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cgdQC$menu_vars_cssmjs))), 'spectrum-Menu-divider')
    }), item.rendered && /*#__PURE__*/ (0, $cgdQC$react).createElement("div", {
        ...headingProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cgdQC$menu_vars_cssmjs))), 'spectrum-Menu-sectionHeading')
    }, item.rendered)), /*#__PURE__*/ (0, $cgdQC$react).createElement("div", {
        ...groupProps,
        style: (0, $cgdQC$layoutInfoToStyle)(layoutInfo, direction),
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cgdQC$menu_vars_cssmjs))), 'spectrum-Menu')
    }, children));
}


export {$35ee08a5b7105872$export$dca12b0bb56e4fc as ListBoxSection};
//# sourceMappingURL=ListBoxSection.js.map
