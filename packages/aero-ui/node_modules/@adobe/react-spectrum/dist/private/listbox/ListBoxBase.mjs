import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import $kfmBr$intlStringsmjs from "./intlStrings.mjs";
import {ListBoxContext as $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d} from "./ListBoxContext.mjs";
import {ListBoxLayout as $1cf0b89a33b93e55$export$c7e5f5ea00052bf} from "./ListBoxLayout.mjs";
import {ListBoxOption as $17e2a30506a3150c$export$feb3b6b552c14a12} from "./ListBoxOption.mjs";
import {ListBoxSection as $0d295233586bb40d$export$dca12b0bb56e4fc} from "./ListBoxSection.mjs";
import {ProgressCircle as $1cfe37e7feefa23d$export$c79b9d6b4cc92af7} from "../progress/ProgressCircle.mjs";
import "../menu_vars.css";
import $kfmBr$menu_vars_cssmjs from "../menu_vars_css.mjs";
import {useProvider as $71dfb0e0358a12de$export$693cdb10cec23617} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useListBox as $kfmBr$useListBox} from "react-aria/useListBox";
import {FocusScope as $kfmBr$FocusScope} from "react-aria/FocusScope";
import {mergeProps as $kfmBr$mergeProps} from "react-aria/mergeProps";
import $kfmBr$react, {useMemo as $kfmBr$useMemo, useCallback as $kfmBr$useCallback, useContext as $kfmBr$useContext} from "react";
import {useLocalizedStringFormatter as $kfmBr$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useObjectRef as $kfmBr$useObjectRef} from "react-aria/useObjectRef";
import {Virtualizer as $kfmBr$Virtualizer} from "react-aria/private/virtualizer/Virtualizer";
import {VirtualizerItem as $kfmBr$VirtualizerItem} from "react-aria/private/virtualizer/VirtualizerItem";


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

















function $ee13b4eccaed924f$export$25768ea656ae32a7() {
    let { scale: scale } = (0, $71dfb0e0358a12de$export$693cdb10cec23617)();
    let layout = (0, $kfmBr$useMemo)(()=>new (0, $1cf0b89a33b93e55$export$c7e5f5ea00052bf)({
            estimatedRowHeight: scale === 'large' ? 48 : 32,
            estimatedHeadingHeight: scale === 'large' ? 33 : 26,
            paddingY: scale === 'large' ? 5 : 4,
            placeholderHeight: scale === 'large' ? 48 : 32
        }), [
        scale
    ]);
    return layout;
}
const $ee13b4eccaed924f$export$1afdcf349979fb7e = /*#__PURE__*/ (0, $kfmBr$react).forwardRef(function ListBoxBase(props, ref) {
    let { layout: layout, state: state, shouldFocusOnHover: shouldFocusOnHover = false, shouldUseVirtualFocus: shouldUseVirtualFocus = false, domProps: domProps = {}, isLoading: isLoading, showLoadingSpinner: showLoadingSpinner = isLoading, onScroll: onScroll, renderEmptyState: renderEmptyState } = props;
    let objectRef = (0, $kfmBr$useObjectRef)(ref);
    let { listBoxProps: listBoxProps } = (0, $kfmBr$useListBox)({
        ...props,
        layoutDelegate: layout,
        isVirtualized: true
    }, state, objectRef);
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let renderWrapper = (0, $kfmBr$useCallback)((parent, reusableView, children, renderChildren)=>{
        if (reusableView.viewType === 'section') return /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $0d295233586bb40d$export$dca12b0bb56e4fc), {
            key: reusableView.key,
            item: reusableView.content,
            layoutInfo: reusableView.layoutInfo,
            virtualizer: reusableView.virtualizer,
            headerLayoutInfo: children.find((c)=>c.viewType === 'header')?.layoutInfo ?? null
        }, renderChildren(children.filter((c)=>c.viewType === 'item')));
        return /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $kfmBr$VirtualizerItem), {
            key: reusableView.key,
            layoutInfo: reusableView.layoutInfo,
            virtualizer: reusableView.virtualizer,
            parent: parent?.layoutInfo
        }, reusableView.rendered);
    }, []);
    let focusedKey = state.selectionManager.focusedKey;
    let persistedKeys = (0, $kfmBr$useMemo)(()=>focusedKey != null ? new Set([
            focusedKey
        ]) : null, [
        focusedKey
    ]);
    return /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d).Provider, {
        value: {
            state: state,
            renderEmptyState: renderEmptyState,
            shouldFocusOnHover: shouldFocusOnHover,
            shouldUseVirtualFocus: shouldUseVirtualFocus
        }
    }, /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $kfmBr$FocusScope), null, /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $kfmBr$Virtualizer), {
        ...styleProps,
        ...(0, $kfmBr$mergeProps)(listBoxProps, domProps),
        ref: objectRef,
        persistedKeys: persistedKeys,
        autoFocus: !!props.autoFocus || undefined,
        scrollDirection: "vertical",
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kfmBr$menu_vars_cssmjs))), 'spectrum-Menu', styleProps.className),
        layout: layout,
        layoutOptions: (0, $kfmBr$useMemo)(()=>({
                isLoading: showLoadingSpinner
            }), [
            showLoadingSpinner
        ]),
        collection: state.collection,
        renderWrapper: renderWrapper,
        isLoading: isLoading,
        onLoadMore: props.onLoadMore,
        onScroll: onScroll
    }, (0, $kfmBr$useCallback)((type, item)=>{
        if (type === 'item') return /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $17e2a30506a3150c$export$feb3b6b552c14a12), {
            item: item
        });
        else if (type === 'loader') return /*#__PURE__*/ (0, $kfmBr$react).createElement($ee13b4eccaed924f$var$LoadingState, null);
        else if (type === 'placeholder') return /*#__PURE__*/ (0, $kfmBr$react).createElement($ee13b4eccaed924f$var$EmptyState, null);
        else return null;
    }, []))));
});
function $ee13b4eccaed924f$var$LoadingState() {
    let { state: state } = (0, $kfmBr$useContext)((0, $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d));
    let stringFormatter = (0, $kfmBr$useLocalizedStringFormatter)((0, ($parcel$interopDefault($kfmBr$intlStringsmjs))), '@react-spectrum/listbox');
    return(// aria-selected isn't needed here since this option is not selectable.
    /*#__PURE__*/ (0, $kfmBr$react).createElement("div", {
        // eslint-disable-next-line jsx-a11y/role-has-required-aria-props
        role: "option",
        style: {
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            height: '100%'
        }
    }, /*#__PURE__*/ (0, $kfmBr$react).createElement((0, $1cfe37e7feefa23d$export$c79b9d6b4cc92af7), {
        isIndeterminate: true,
        size: "S",
        "aria-label": state.collection.size > 0 ? stringFormatter.format('loadingMore') : stringFormatter.format('loading'),
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($kfmBr$menu_vars_cssmjs))), 'spectrum-Dropdown-progressCircle')
    })));
}
function $ee13b4eccaed924f$var$EmptyState() {
    let { renderEmptyState: renderEmptyState } = (0, $kfmBr$useContext)((0, $200f4a7d8a01d3bb$export$7ff8f37d2d81a48d));
    let emptyState = renderEmptyState ? renderEmptyState() : null;
    if (emptyState == null) return null;
    return /*#__PURE__*/ (0, $kfmBr$react).createElement("div", {
        // aria-selected isn't needed here since this option is not selectable.
        // eslint-disable-next-line jsx-a11y/role-has-required-aria-props
        role: "option"
    }, emptyState);
}


export {$ee13b4eccaed924f$export$25768ea656ae32a7 as useListBoxLayout, $ee13b4eccaed924f$export$1afdcf349979fb7e as ListBoxBase};
//# sourceMappingURL=ListBoxBase.mjs.map
