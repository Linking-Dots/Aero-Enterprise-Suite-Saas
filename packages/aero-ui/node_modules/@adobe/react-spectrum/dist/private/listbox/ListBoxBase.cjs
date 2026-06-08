var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $daca6c13ca89ba55$exports = require("./intlStrings.cjs");
var $b02f16a34f83c86b$exports = require("./ListBoxContext.cjs");
var $b660b0e98da9950e$exports = require("./ListBoxLayout.cjs");
var $297e061fd2890b3d$exports = require("./ListBoxOption.cjs");
var $07d691d60ee24aa0$exports = require("./ListBoxSection.cjs");
var $948c2416aa3a9507$exports = require("../progress/ProgressCircle.cjs");
require("../menu_vars.css");
var $35d34152ff885d5c$exports = require("../menu_vars_css.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $jqD6t$reactariauseListBox = require("react-aria/useListBox");
var $jqD6t$reactariaFocusScope = require("react-aria/FocusScope");
var $jqD6t$reactariamergeProps = require("react-aria/mergeProps");
var $jqD6t$react = require("react");
var $jqD6t$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");
var $jqD6t$reactariauseObjectRef = require("react-aria/useObjectRef");
var $jqD6t$reactariaprivatevirtualizerVirtualizer = require("react-aria/private/virtualizer/Virtualizer");
var $jqD6t$reactariaprivatevirtualizerVirtualizerItem = require("react-aria/private/virtualizer/VirtualizerItem");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "useListBoxLayout", function () { return $cb7ee1d9d5613db9$export$25768ea656ae32a7; });
$parcel$export(module.exports, "ListBoxBase", function () { return $cb7ee1d9d5613db9$export$1afdcf349979fb7e; });
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

















function $cb7ee1d9d5613db9$export$25768ea656ae32a7() {
    let { scale: scale } = (0, $544fc82701fc93e9$exports.useProvider)();
    let layout = (0, $jqD6t$react.useMemo)(()=>new (0, $b660b0e98da9950e$exports.ListBoxLayout)({
            estimatedRowHeight: scale === 'large' ? 48 : 32,
            estimatedHeadingHeight: scale === 'large' ? 33 : 26,
            paddingY: scale === 'large' ? 5 : 4,
            placeholderHeight: scale === 'large' ? 48 : 32
        }), [
        scale
    ]);
    return layout;
}
const $cb7ee1d9d5613db9$export$1afdcf349979fb7e = /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).forwardRef(function ListBoxBase(props, ref) {
    let { layout: layout, state: state, shouldFocusOnHover: shouldFocusOnHover = false, shouldUseVirtualFocus: shouldUseVirtualFocus = false, domProps: domProps = {}, isLoading: isLoading, showLoadingSpinner: showLoadingSpinner = isLoading, onScroll: onScroll, renderEmptyState: renderEmptyState } = props;
    let objectRef = (0, $jqD6t$reactariauseObjectRef.useObjectRef)(ref);
    let { listBoxProps: listBoxProps } = (0, $jqD6t$reactariauseListBox.useListBox)({
        ...props,
        layoutDelegate: layout,
        isVirtualized: true
    }, state, objectRef);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let renderWrapper = (0, $jqD6t$react.useCallback)((parent, reusableView, children, renderChildren)=>{
        if (reusableView.viewType === 'section') return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $07d691d60ee24aa0$exports.ListBoxSection), {
            key: reusableView.key,
            item: reusableView.content,
            layoutInfo: reusableView.layoutInfo,
            virtualizer: reusableView.virtualizer,
            headerLayoutInfo: children.find((c)=>c.viewType === 'header')?.layoutInfo ?? null
        }, renderChildren(children.filter((c)=>c.viewType === 'item')));
        return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $jqD6t$reactariaprivatevirtualizerVirtualizerItem.VirtualizerItem), {
            key: reusableView.key,
            layoutInfo: reusableView.layoutInfo,
            virtualizer: reusableView.virtualizer,
            parent: parent?.layoutInfo
        }, reusableView.rendered);
    }, []);
    let focusedKey = state.selectionManager.focusedKey;
    let persistedKeys = (0, $jqD6t$react.useMemo)(()=>focusedKey != null ? new Set([
            focusedKey
        ]) : null, [
        focusedKey
    ]);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $b02f16a34f83c86b$exports.ListBoxContext).Provider, {
        value: {
            state: state,
            renderEmptyState: renderEmptyState,
            shouldFocusOnHover: shouldFocusOnHover,
            shouldUseVirtualFocus: shouldUseVirtualFocus
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $jqD6t$reactariaFocusScope.FocusScope), null, /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $jqD6t$reactariaprivatevirtualizerVirtualizer.Virtualizer), {
        ...styleProps,
        ...(0, $jqD6t$reactariamergeProps.mergeProps)(listBoxProps, domProps),
        ref: objectRef,
        persistedKeys: persistedKeys,
        autoFocus: !!props.autoFocus || undefined,
        scrollDirection: "vertical",
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Menu', styleProps.className),
        layout: layout,
        layoutOptions: (0, $jqD6t$react.useMemo)(()=>({
                isLoading: showLoadingSpinner
            }), [
            showLoadingSpinner
        ]),
        collection: state.collection,
        renderWrapper: renderWrapper,
        isLoading: isLoading,
        onLoadMore: props.onLoadMore,
        onScroll: onScroll
    }, (0, $jqD6t$react.useCallback)((type, item)=>{
        if (type === 'item') return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $297e061fd2890b3d$exports.ListBoxOption), {
            item: item
        });
        else if (type === 'loader') return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement($cb7ee1d9d5613db9$var$LoadingState, null);
        else if (type === 'placeholder') return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement($cb7ee1d9d5613db9$var$EmptyState, null);
        else return null;
    }, []))));
});
function $cb7ee1d9d5613db9$var$LoadingState() {
    let { state: state } = (0, $jqD6t$react.useContext)((0, $b02f16a34f83c86b$exports.ListBoxContext));
    let stringFormatter = (0, $jqD6t$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($daca6c13ca89ba55$exports))), '@react-spectrum/listbox');
    return(// aria-selected isn't needed here since this option is not selectable.
    /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement("div", {
        // eslint-disable-next-line jsx-a11y/role-has-required-aria-props
        role: "option",
        style: {
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            height: '100%'
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement((0, $948c2416aa3a9507$exports.ProgressCircle), {
        isIndeterminate: true,
        size: "S",
        "aria-label": state.collection.size > 0 ? stringFormatter.format('loadingMore') : stringFormatter.format('loading'),
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($35d34152ff885d5c$exports))), 'spectrum-Dropdown-progressCircle')
    })));
}
function $cb7ee1d9d5613db9$var$EmptyState() {
    let { renderEmptyState: renderEmptyState } = (0, $jqD6t$react.useContext)((0, $b02f16a34f83c86b$exports.ListBoxContext));
    let emptyState = renderEmptyState ? renderEmptyState() : null;
    if (emptyState == null) return null;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jqD6t$react))).createElement("div", {
        // aria-selected isn't needed here since this option is not selectable.
        // eslint-disable-next-line jsx-a11y/role-has-required-aria-props
        role: "option"
    }, emptyState);
}


//# sourceMappingURL=ListBoxBase.cjs.map
