import {ButtonContext as $fc203795b9b363cd$export$24d547caef80ccd1} from "./Button.js";
import {dom as $b7b7a92703138c9b$export$df3a06d6289f983e, Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useContextProps as $b7b7a92703138c9b$export$29f1550f4b0d4415, useRenderProps as $b7b7a92703138c9b$export$4d86445c2cf5e3, useSlot as $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {FieldErrorContext as $6567560e1d9cc847$export$ff05c3ac10437e03} from "./FieldError.js";
import {FormContext as $c7332d4a2d191cd2$export$c24727297075ec6a} from "./Form.js";
import $2OPXg$intlStringsjs from "./intlStrings.js";
import {LabelContext as $3e4839e5b30e7b17$export$75b6ee27786ba447} from "./Label.js";
import {ListBoxContext as $ba3142315b3e1149$export$7ff8f37d2d81a48d, ListStateContext as $ba3142315b3e1149$export$7c5906fe4f1f2af2} from "./ListBox.js";
import {OverlayTriggerStateContext as $acf8e70c2f419f18$export$d2f961adcb0afbe} from "./Dialog.js";
import {PopoverContext as $03df2f2d3cffb62f$export$9b9a0cd73afb7ca4} from "./Popover.js";
import {TextContext as $20d769b1e2b13352$export$9afb8bc826b033ea} from "./Text.js";
import {useSelect as $2OPXg$useSelect, HiddenSelect as $2OPXg$HiddenSelect} from "react-aria/useSelect";
import {CollectionBuilder as $2OPXg$CollectionBuilder} from "react-aria/CollectionBuilder";
import {createHideableComponent as $2OPXg$createHideableComponent} from "react-aria/private/collections/Hidden";
import {filterDOMProps as $2OPXg$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $2OPXg$mergeProps} from "react-aria/mergeProps";
import $2OPXg$react, {createContext as $2OPXg$createContext, useMemo as $2OPXg$useMemo, useRef as $2OPXg$useRef, useContext as $2OPXg$useContext, Fragment as $2OPXg$Fragment} from "react";
import {useSelectState as $2OPXg$useSelectState} from "react-stately/useSelectState";
import {useFocusRing as $2OPXg$useFocusRing} from "react-aria/useFocusRing";
import {useListFormatter as $2OPXg$useListFormatter} from "react-aria/useListFormatter";
import {useLocalizedStringFormatter as $2OPXg$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 



















const $5ade0166931ef32f$export$7540cee5be7dc19b = /*#__PURE__*/ (0, $2OPXg$createContext)(null);
const $5ade0166931ef32f$export$ef445b55be0601bd = /*#__PURE__*/ (0, $2OPXg$createContext)(null);
const $5ade0166931ef32f$export$ef9b1a59e592288f = /*#__PURE__*/ (0, $2OPXg$createHideableComponent)(function Select(props, ref) {
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $5ade0166931ef32f$export$7540cee5be7dc19b);
    let { children: children, isDisabled: isDisabled = false, isInvalid: isInvalid = false, isRequired: isRequired = false } = props;
    let content = (0, $2OPXg$useMemo)(()=>typeof children === 'function' ? children({
            isOpen: false,
            isDisabled: isDisabled,
            isInvalid: isInvalid,
            isRequired: isRequired,
            isFocused: false,
            isFocusVisible: false,
            defaultChildren: null
        }) : children, [
        children,
        isDisabled,
        isInvalid,
        isRequired
    ]);
    return /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $2OPXg$CollectionBuilder), {
        content: content
    }, (collection)=>/*#__PURE__*/ (0, $2OPXg$react).createElement($5ade0166931ef32f$var$SelectInner, {
            props: props,
            collection: collection,
            selectRef: ref
        }));
});
// Contexts to clear inside the popover.
const $5ade0166931ef32f$var$CLEAR_CONTEXTS = [
    (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
    (0, $fc203795b9b363cd$export$24d547caef80ccd1),
    (0, $20d769b1e2b13352$export$9afb8bc826b033ea)
];
function $5ade0166931ef32f$var$SelectInner({ props: props, selectRef: ref, collection: collection }) {
    let { validationBehavior: formValidationBehavior } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)((0, $c7332d4a2d191cd2$export$c24727297075ec6a)) || {};
    var _props_validationBehavior, _ref;
    let validationBehavior = (_ref = (_props_validationBehavior = props.validationBehavior) !== null && _props_validationBehavior !== void 0 ? _props_validationBehavior : formValidationBehavior) !== null && _ref !== void 0 ? _ref : 'native';
    let state = (0, $2OPXg$useSelectState)({
        ...props,
        collection: collection,
        children: undefined,
        validationBehavior: validationBehavior
    });
    let { isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $2OPXg$useFocusRing)({
        within: true
    });
    // Get props for child elements from useSelect
    let buttonRef = (0, $2OPXg$useRef)(null);
    let [labelRef, label] = (0, $b7b7a92703138c9b$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, triggerProps: triggerProps, valueProps: valueProps, menuProps: menuProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, hiddenSelectProps: hiddenSelectProps, ...validation } = (0, $2OPXg$useSelect)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, buttonRef);
    // Only expose a subset of state to renderProps function to avoid infinite render loop
    let renderPropsState = (0, $2OPXg$useMemo)(()=>({
            isOpen: state.isOpen,
            isFocused: state.isFocused,
            isFocusVisible: isFocusVisible,
            isDisabled: props.isDisabled || false,
            isInvalid: validation.isInvalid || false,
            isRequired: props.isRequired || false
        }), [
        state.isOpen,
        state.isFocused,
        isFocusVisible,
        props.isDisabled,
        validation.isInvalid,
        props.isRequired
    ]);
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        values: renderPropsState,
        defaultClassName: 'react-aria-Select'
    });
    let DOMProps = (0, $2OPXg$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    let scrollRef = (0, $2OPXg$useRef)(null);
    return /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $5ade0166931ef32f$export$7540cee5be7dc19b,
                props
            ],
            [
                $5ade0166931ef32f$export$ef445b55be0601bd,
                state
            ],
            [
                $5ade0166931ef32f$export$f8f745c04421623f,
                valueProps
            ],
            [
                (0, $3e4839e5b30e7b17$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $fc203795b9b363cd$export$24d547caef80ccd1),
                {
                    ...triggerProps,
                    ref: buttonRef,
                    isPressed: state.isOpen,
                    autoFocus: props.autoFocus
                }
            ],
            [
                (0, $acf8e70c2f419f18$export$d2f961adcb0afbe),
                state
            ],
            [
                (0, $03df2f2d3cffb62f$export$9b9a0cd73afb7ca4),
                {
                    trigger: 'Select',
                    triggerRef: buttonRef,
                    scrollRef: scrollRef,
                    placement: 'bottom start',
                    'aria-labelledby': menuProps['aria-labelledby'],
                    clearContexts: $5ade0166931ef32f$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $ba3142315b3e1149$export$7ff8f37d2d81a48d),
                {
                    ...menuProps,
                    ref: scrollRef
                }
            ],
            [
                (0, $ba3142315b3e1149$export$7c5906fe4f1f2af2),
                state
            ],
            [
                (0, $20d769b1e2b13352$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $6567560e1d9cc847$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).div, {
        ...(0, $2OPXg$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": state.isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-open": state.isOpen || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, renderProps.children, /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $2OPXg$HiddenSelect), {
        ...hiddenSelectProps,
        autoComplete: props.autoComplete
    })));
}
const $5ade0166931ef32f$export$f8f745c04421623f = /*#__PURE__*/ (0, $2OPXg$createContext)(null);
const $5ade0166931ef32f$export$e288731fd71264f0 = /*#__PURE__*/ (0, $2OPXg$createHideableComponent)(function SelectValue(props, ref) {
    var _state_selectedItems_;
    [props, ref] = (0, $b7b7a92703138c9b$export$29f1550f4b0d4415)(props, ref, $5ade0166931ef32f$export$f8f745c04421623f);
    let state = (0, $2OPXg$useContext)($5ade0166931ef32f$export$ef445b55be0601bd);
    let { placeholder: placeholder } = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)($5ade0166931ef32f$export$7540cee5be7dc19b);
    let rendered = state.selectedItems.map((item)=>{
        var _item_props;
        let rendered = (_item_props = item.props) === null || _item_props === void 0 ? void 0 : _item_props.children;
        // If the selected item has a function as a child, we need to call it to render to React.JSX.
        if (typeof rendered === 'function') {
            let fn = rendered;
            rendered = fn({
                isHovered: false,
                isPressed: false,
                isSelected: false,
                isFocused: false,
                isFocusVisible: false,
                isDisabled: false,
                selectionMode: 'single',
                selectionBehavior: 'toggle'
            });
        }
        return rendered;
    });
    let formatter = (0, $2OPXg$useListFormatter)();
    let textValue = (0, $2OPXg$useMemo)(()=>state.selectedItems.map((item)=>item === null || item === void 0 ? void 0 : item.textValue), [
        state.selectedItems
    ]);
    let selectionMode = state.selectionManager.selectionMode;
    let selectedText = (0, $2OPXg$useMemo)(()=>{
        var _textValue_;
        return selectionMode === 'single' ? (_textValue_ = textValue[0]) !== null && _textValue_ !== void 0 ? _textValue_ : '' : formatter.format(textValue);
    }, [
        selectionMode,
        formatter,
        textValue
    ]);
    let defaultChildren = (0, $2OPXg$useMemo)(()=>{
        if (selectionMode === 'single') return rendered[0];
        let parts = formatter.formatToParts(textValue);
        if (parts.length === 0) return null;
        let index = 0;
        return parts.map((part)=>{
            if (part.type === 'element') return /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $2OPXg$Fragment), {
                key: index
            }, rendered[index++]);
            else return part.value;
        });
    }, [
        selectionMode,
        formatter,
        textValue,
        rendered
    ]);
    let stringFormatter = (0, $2OPXg$useLocalizedStringFormatter)((0, ($parcel$interopDefault($2OPXg$intlStringsjs))), 'react-aria-components');
    var _ref, _state_selectedItems__value;
    let renderProps = (0, $b7b7a92703138c9b$export$4d86445c2cf5e3)({
        ...props,
        defaultChildren: (_ref = defaultChildren !== null && defaultChildren !== void 0 ? defaultChildren : placeholder) !== null && _ref !== void 0 ? _ref : stringFormatter.format('selectPlaceholder'),
        defaultClassName: 'react-aria-SelectValue',
        values: {
            selectedItem: (_state_selectedItems__value = (_state_selectedItems_ = state.selectedItems[0]) === null || _state_selectedItems_ === void 0 ? void 0 : _state_selectedItems_.value) !== null && _state_selectedItems__value !== void 0 ? _state_selectedItems__value : null,
            selectedItems: (0, $2OPXg$useMemo)(()=>state.selectedItems.map((item)=>{
                    var _item_value;
                    return (_item_value = item.value) !== null && _item_value !== void 0 ? _item_value : null;
                }), [
                state.selectedItems
            ]),
            selectedText: selectedText,
            isPlaceholder: state.selectedItems.length === 0,
            state: state
        }
    });
    let DOMProps = (0, $2OPXg$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $b7b7a92703138c9b$export$df3a06d6289f983e).span, {
        ref: ref,
        ...DOMProps,
        ...renderProps,
        "data-placeholder": state.selectedItems.length === 0 || undefined
    }, /*#__PURE__*/ (0, $2OPXg$react).createElement((0, $20d769b1e2b13352$export$9afb8bc826b033ea).Provider, {
        value: undefined
    }, renderProps.children));
});


export {$5ade0166931ef32f$export$7540cee5be7dc19b as SelectContext, $5ade0166931ef32f$export$ef445b55be0601bd as SelectStateContext, $5ade0166931ef32f$export$ef9b1a59e592288f as Select, $5ade0166931ef32f$export$f8f745c04421623f as SelectValueContext, $5ade0166931ef32f$export$e288731fd71264f0 as SelectValue};
//# sourceMappingURL=Select.js.map
