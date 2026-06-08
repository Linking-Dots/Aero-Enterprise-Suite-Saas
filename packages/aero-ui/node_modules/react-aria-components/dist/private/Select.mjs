import {ButtonContext as $7705c033048f6da7$export$24d547caef80ccd1} from "./Button.mjs";
import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3, useSlot as $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {FieldErrorContext as $1f3c3b1a70cec653$export$ff05c3ac10437e03} from "./FieldError.mjs";
import {FormContext as $cdaed739b1139372$export$c24727297075ec6a} from "./Form.mjs";
import $2l4Cq$intlStringsmjs from "./intlStrings.mjs";
import {LabelContext as $43a3b93638fe5db9$export$75b6ee27786ba447} from "./Label.mjs";
import {ListBoxContext as $928221da08ecbc62$export$7ff8f37d2d81a48d, ListStateContext as $928221da08ecbc62$export$7c5906fe4f1f2af2} from "./ListBox.mjs";
import {OverlayTriggerStateContext as $f2ff30fde7b014be$export$d2f961adcb0afbe} from "./Dialog.mjs";
import {PopoverContext as $542a13ca2fa5b484$export$9b9a0cd73afb7ca4} from "./Popover.mjs";
import {TextContext as $efe09c6d1c304b50$export$9afb8bc826b033ea} from "./Text.mjs";
import {useSelect as $2l4Cq$useSelect, HiddenSelect as $2l4Cq$HiddenSelect} from "react-aria/useSelect";
import {CollectionBuilder as $2l4Cq$CollectionBuilder} from "react-aria/CollectionBuilder";
import {createHideableComponent as $2l4Cq$createHideableComponent} from "react-aria/private/collections/Hidden";
import {filterDOMProps as $2l4Cq$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $2l4Cq$mergeProps} from "react-aria/mergeProps";
import $2l4Cq$react, {createContext as $2l4Cq$createContext, useMemo as $2l4Cq$useMemo, useRef as $2l4Cq$useRef, useContext as $2l4Cq$useContext, Fragment as $2l4Cq$Fragment} from "react";
import {useSelectState as $2l4Cq$useSelectState} from "react-stately/useSelectState";
import {useFocusRing as $2l4Cq$useFocusRing} from "react-aria/useFocusRing";
import {useListFormatter as $2l4Cq$useListFormatter} from "react-aria/useListFormatter";
import {useLocalizedStringFormatter as $2l4Cq$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";


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



















const $c8bb816105474884$export$7540cee5be7dc19b = /*#__PURE__*/ (0, $2l4Cq$createContext)(null);
const $c8bb816105474884$export$ef445b55be0601bd = /*#__PURE__*/ (0, $2l4Cq$createContext)(null);
const $c8bb816105474884$export$ef9b1a59e592288f = /*#__PURE__*/ (0, $2l4Cq$createHideableComponent)(function Select(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $c8bb816105474884$export$7540cee5be7dc19b);
    let { children: children, isDisabled: isDisabled = false, isInvalid: isInvalid = false, isRequired: isRequired = false } = props;
    let content = (0, $2l4Cq$useMemo)(()=>typeof children === 'function' ? children({
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
    return /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $2l4Cq$CollectionBuilder), {
        content: content
    }, (collection)=>/*#__PURE__*/ (0, $2l4Cq$react).createElement($c8bb816105474884$var$SelectInner, {
            props: props,
            collection: collection,
            selectRef: ref
        }));
});
// Contexts to clear inside the popover.
const $c8bb816105474884$var$CLEAR_CONTEXTS = [
    (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
    (0, $7705c033048f6da7$export$24d547caef80ccd1),
    (0, $efe09c6d1c304b50$export$9afb8bc826b033ea)
];
function $c8bb816105474884$var$SelectInner({ props: props, selectRef: ref, collection: collection }) {
    let { validationBehavior: formValidationBehavior } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)((0, $cdaed739b1139372$export$c24727297075ec6a)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $2l4Cq$useSelectState)({
        ...props,
        collection: collection,
        children: undefined,
        validationBehavior: validationBehavior
    });
    let { isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $2l4Cq$useFocusRing)({
        within: true
    });
    // Get props for child elements from useSelect
    let buttonRef = (0, $2l4Cq$useRef)(null);
    let [labelRef, label] = (0, $7230ffa83bc0c2cf$export$9d4c57ee4c6ffdd8)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, triggerProps: triggerProps, valueProps: valueProps, menuProps: menuProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, hiddenSelectProps: hiddenSelectProps, ...validation } = (0, $2l4Cq$useSelect)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, buttonRef);
    // Only expose a subset of state to renderProps function to avoid infinite render loop
    let renderPropsState = (0, $2l4Cq$useMemo)(()=>({
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
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: renderPropsState,
        defaultClassName: 'react-aria-Select'
    });
    let DOMProps = (0, $2l4Cq$filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    let scrollRef = (0, $2l4Cq$useRef)(null);
    return /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $c8bb816105474884$export$7540cee5be7dc19b,
                props
            ],
            [
                $c8bb816105474884$export$ef445b55be0601bd,
                state
            ],
            [
                $c8bb816105474884$export$f8f745c04421623f,
                valueProps
            ],
            [
                (0, $43a3b93638fe5db9$export$75b6ee27786ba447),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $7705c033048f6da7$export$24d547caef80ccd1),
                {
                    ...triggerProps,
                    ref: buttonRef,
                    isPressed: state.isOpen,
                    autoFocus: props.autoFocus
                }
            ],
            [
                (0, $f2ff30fde7b014be$export$d2f961adcb0afbe),
                state
            ],
            [
                (0, $542a13ca2fa5b484$export$9b9a0cd73afb7ca4),
                {
                    trigger: 'Select',
                    triggerRef: buttonRef,
                    scrollRef: scrollRef,
                    placement: 'bottom start',
                    'aria-labelledby': menuProps['aria-labelledby'],
                    clearContexts: $c8bb816105474884$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $928221da08ecbc62$export$7ff8f37d2d81a48d),
                {
                    ...menuProps,
                    ref: scrollRef
                }
            ],
            [
                (0, $928221da08ecbc62$export$7c5906fe4f1f2af2),
                state
            ],
            [
                (0, $efe09c6d1c304b50$export$9afb8bc826b033ea),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $1f3c3b1a70cec653$export$ff05c3ac10437e03),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $2l4Cq$mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": state.isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-open": state.isOpen || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, renderProps.children, /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $2l4Cq$HiddenSelect), {
        ...hiddenSelectProps,
        autoComplete: props.autoComplete
    })));
}
const $c8bb816105474884$export$f8f745c04421623f = /*#__PURE__*/ (0, $2l4Cq$createContext)(null);
const $c8bb816105474884$export$e288731fd71264f0 = /*#__PURE__*/ (0, $2l4Cq$createHideableComponent)(function SelectValue(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $c8bb816105474884$export$f8f745c04421623f);
    let state = (0, $2l4Cq$useContext)($c8bb816105474884$export$ef445b55be0601bd);
    let { placeholder: placeholder } = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($c8bb816105474884$export$7540cee5be7dc19b);
    let rendered = state.selectedItems.map((item)=>{
        let rendered = item.props?.children;
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
    let formatter = (0, $2l4Cq$useListFormatter)();
    let textValue = (0, $2l4Cq$useMemo)(()=>state.selectedItems.map((item)=>item?.textValue), [
        state.selectedItems
    ]);
    let selectionMode = state.selectionManager.selectionMode;
    let selectedText = (0, $2l4Cq$useMemo)(()=>selectionMode === 'single' ? textValue[0] ?? '' : formatter.format(textValue), [
        selectionMode,
        formatter,
        textValue
    ]);
    let defaultChildren = (0, $2l4Cq$useMemo)(()=>{
        if (selectionMode === 'single') return rendered[0];
        let parts = formatter.formatToParts(textValue);
        if (parts.length === 0) return null;
        let index = 0;
        return parts.map((part)=>{
            if (part.type === 'element') return /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $2l4Cq$Fragment), {
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
    let stringFormatter = (0, $2l4Cq$useLocalizedStringFormatter)((0, ($parcel$interopDefault($2l4Cq$intlStringsmjs))), 'react-aria-components');
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        defaultChildren: defaultChildren ?? placeholder ?? stringFormatter.format('selectPlaceholder'),
        defaultClassName: 'react-aria-SelectValue',
        values: {
            selectedItem: state.selectedItems[0]?.value ?? null,
            selectedItems: (0, $2l4Cq$useMemo)(()=>state.selectedItems.map((item)=>item.value ?? null), [
                state.selectedItems
            ]),
            selectedText: selectedText,
            isPlaceholder: state.selectedItems.length === 0,
            state: state
        }
    });
    let DOMProps = (0, $2l4Cq$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).span, {
        ref: ref,
        ...DOMProps,
        ...renderProps,
        "data-placeholder": state.selectedItems.length === 0 || undefined
    }, /*#__PURE__*/ (0, $2l4Cq$react).createElement((0, $efe09c6d1c304b50$export$9afb8bc826b033ea).Provider, {
        value: undefined
    }, renderProps.children));
});


export {$c8bb816105474884$export$7540cee5be7dc19b as SelectContext, $c8bb816105474884$export$ef445b55be0601bd as SelectStateContext, $c8bb816105474884$export$ef9b1a59e592288f as Select, $c8bb816105474884$export$f8f745c04421623f as SelectValueContext, $c8bb816105474884$export$e288731fd71264f0 as SelectValue};
//# sourceMappingURL=Select.mjs.map
