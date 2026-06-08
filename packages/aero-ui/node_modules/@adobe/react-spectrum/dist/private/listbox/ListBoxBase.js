import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import $cAm8F$intlStringsjs from "./intlStrings.js";
import {ListBoxContext as $90de0e4b1949420b$export$7ff8f37d2d81a48d} from "./ListBoxContext.js";
import {ListBoxLayout as $83307db3d5713315$export$c7e5f5ea00052bf} from "./ListBoxLayout.js";
import {ListBoxOption as $1ff51a8d0dceabe5$export$feb3b6b552c14a12} from "./ListBoxOption.js";
import {ListBoxSection as $35ee08a5b7105872$export$dca12b0bb56e4fc} from "./ListBoxSection.js";
import {ProgressCircle as $277696409c391eff$export$c79b9d6b4cc92af7} from "../progress/ProgressCircle.js";
import "../menu_vars.css";
import $cAm8F$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {useProvider as $089943c7a219141c$export$693cdb10cec23617} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useListBox as $cAm8F$useListBox} from "react-aria/useListBox";
import {FocusScope as $cAm8F$FocusScope} from "react-aria/FocusScope";
import {mergeProps as $cAm8F$mergeProps} from "react-aria/mergeProps";
import $cAm8F$react, {useMemo as $cAm8F$useMemo, useCallback as $cAm8F$useCallback, useContext as $cAm8F$useContext} from "react";
import {useLocalizedStringFormatter as $cAm8F$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useObjectRef as $cAm8F$useObjectRef} from "react-aria/useObjectRef";
import {Virtualizer as $cAm8F$Virtualizer} from "react-aria/private/virtualizer/Virtualizer";
import {VirtualizerItem as $cAm8F$VirtualizerItem} from "react-aria/private/virtualizer/VirtualizerItem";


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

















function $45f8932a4e549cb6$export$25768ea656ae32a7() {
    let { scale: scale } = (0, $089943c7a219141c$export$693cdb10cec23617)();
    let layout = (0, $cAm8F$useMemo)(()=>new (0, $83307db3d5713315$export$c7e5f5ea00052bf)({
            estimatedRowHeight: scale === 'large' ? 48 : 32,
            estimatedHeadingHeight: scale === 'large' ? 33 : 26,
            paddingY: scale === 'large' ? 5 : 4,
            placeholderHeight: scale === 'large' ? 48 : 32
        }), [
        scale
    ]);
    return layout;
}
const $45f8932a4e549cb6$export$1afdcf349979fb7e = /*#__PURE__*/ (0, $cAm8F$react).forwardRef(function ListBoxBase(props, ref) {
    let { layout: layout, state: state, shouldFocusOnHover: shouldFocusOnHover = false, shouldUseVirtualFocus: shouldUseVirtualFocus = false, domProps: domProps = {}, isLoading: isLoading, showLoadingSpinner: showLoadingSpinner = isLoading, onScroll: onScroll, renderEmptyState: renderEmptyState } = props;
    let objectRef = (0, $cAm8F$useObjectRef)(ref);
    let { listBoxProps: listBoxProps } = (0, $cAm8F$useListBox)({
        ...props,
        layoutDelegate: layout,
        isVirtualized: true
    }, state, objectRef);
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let renderWrapper = (0, $cAm8F$useCallback)((parent, reusableView, children, renderChildren)=>{
        var _children_find;
        var _children_find_layoutInfo;
        if (reusableView.viewType === 'section') return /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $35ee08a5b7105872$export$dca12b0bb56e4fc), {
            key: reusableView.key,
            item: reusableView.content,
            layoutInfo: reusableView.layoutInfo,
            virtualizer: reusableView.virtualizer,
            headerLayoutInfo: (_children_find_layoutInfo = (_children_find = children.find((c)=>c.viewType === 'header')) === null || _children_find === void 0 ? void 0 : _children_find.layoutInfo) !== null && _children_find_layoutInfo !== void 0 ? _children_find_layoutInfo : null
        }, renderChildren(children.filter((c)=>c.viewType === 'item')));
        return /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $cAm8F$VirtualizerItem), {
            key: reusableView.key,
            layoutInfo: reusableView.layoutInfo,
            virtualizer: reusableView.virtualizer,
            parent: parent === null || parent === void 0 ? void 0 : parent.layoutInfo
        }, reusableView.rendered);
    }, []);
    let focusedKey = state.selectionManager.focusedKey;
    let persistedKeys = (0, $cAm8F$useMemo)(()=>focusedKey != null ? new Set([
            focusedKey
        ]) : null, [
        focusedKey
    ]);
    return /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $90de0e4b1949420b$export$7ff8f37d2d81a48d).Provider, {
        value: {
            state: state,
            renderEmptyState: renderEmptyState,
            shouldFocusOnHover: shouldFocusOnHover,
            shouldUseVirtualFocus: shouldUseVirtualFocus
        }
    }, /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $cAm8F$FocusScope), null, /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $cAm8F$Virtualizer), {
        ...styleProps,
        ...(0, $cAm8F$mergeProps)(listBoxProps, domProps),
        ref: objectRef,
        persistedKeys: persistedKeys,
        autoFocus: !!props.autoFocus || undefined,
        scrollDirection: "vertical",
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cAm8F$menu_vars_cssmjs))), 'spectrum-Menu', styleProps.className),
        layout: layout,
        layoutOptions: (0, $cAm8F$useMemo)(()=>({
                isLoading: showLoadingSpinner
            }), [
            showLoadingSpinner
        ]),
        collection: state.collection,
        renderWrapper: renderWrapper,
        isLoading: isLoading,
        onLoadMore: props.onLoadMore,
        onScroll: onScroll
    }, (0, $cAm8F$useCallback)((type, item)=>{
        if (type === 'item') return /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $1ff51a8d0dceabe5$export$feb3b6b552c14a12), {
            item: item
        });
        else if (type === 'loader') return /*#__PURE__*/ (0, $cAm8F$react).createElement($45f8932a4e549cb6$var$LoadingState, null);
        else if (type === 'placeholder') return /*#__PURE__*/ (0, $cAm8F$react).createElement($45f8932a4e549cb6$var$EmptyState, null);
        else return null;
    }, []))));
});
function $45f8932a4e549cb6$var$LoadingState() {
    let { state: state } = (0, $cAm8F$useContext)((0, $90de0e4b1949420b$export$7ff8f37d2d81a48d));
    let stringFormatter = (0, $cAm8F$useLocalizedStringFormatter)((0, ($parcel$interopDefault($cAm8F$intlStringsjs))), '@react-spectrum/listbox');
    return(// aria-selected isn't needed here since this option is not selectable.
    /*#__PURE__*/ (0, $cAm8F$react).createElement("div", {
        // eslint-disable-next-line jsx-a11y/role-has-required-aria-props
        role: "option",
        style: {
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            height: '100%'
        }
    }, /*#__PURE__*/ (0, $cAm8F$react).createElement((0, $277696409c391eff$export$c79b9d6b4cc92af7), {
        isIndeterminate: true,
        size: "S",
        "aria-label": state.collection.size > 0 ? stringFormatter.format('loadingMore') : stringFormatter.format('loading'),
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($cAm8F$menu_vars_cssmjs))), 'spectrum-Dropdown-progressCircle')
    })));
}
function $45f8932a4e549cb6$var$EmptyState() {
    let { renderEmptyState: renderEmptyState } = (0, $cAm8F$useContext)((0, $90de0e4b1949420b$export$7ff8f37d2d81a48d));
    let emptyState = renderEmptyState ? renderEmptyState() : null;
    if (emptyState == null) return null;
    return /*#__PURE__*/ (0, $cAm8F$react).createElement("div", {
        // aria-selected isn't needed here since this option is not selectable.
        // eslint-disable-next-line jsx-a11y/role-has-required-aria-props
        role: "option"
    }, emptyState);
}


export {$45f8932a4e549cb6$export$25768ea656ae32a7 as useListBoxLayout, $45f8932a4e549cb6$export$1afdcf349979fb7e as ListBoxBase};
//# sourceMappingURL=ListBoxBase.js.map
