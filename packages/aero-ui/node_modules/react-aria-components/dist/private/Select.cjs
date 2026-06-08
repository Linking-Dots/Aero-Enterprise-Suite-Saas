var $16c7f9b22cce3838$exports = require("./Button.cjs");
var $048d76b84370f141$exports = require("./utils.cjs");
var $862aa7df04d8fa76$exports = require("./FieldError.cjs");
var $5adc12e2ce73be8f$exports = require("./Form.cjs");
var $5724b511a2687756$exports = require("./intlStrings.cjs");
var $d5d46822336ca1e1$exports = require("./Label.cjs");
var $537333b300f7e667$exports = require("./ListBox.cjs");
var $88595bf043e542ec$exports = require("./Dialog.cjs");
var $74e35a768d38d46b$exports = require("./Popover.cjs");
var $cab7d9a238d19c33$exports = require("./Text.cjs");
var $75P61$reactariauseSelect = require("react-aria/useSelect");
var $75P61$reactariaCollectionBuilder = require("react-aria/CollectionBuilder");
var $75P61$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $75P61$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $75P61$reactariamergeProps = require("react-aria/mergeProps");
var $75P61$react = require("react");
var $75P61$reactstatelyuseSelectState = require("react-stately/useSelectState");
var $75P61$reactariauseFocusRing = require("react-aria/useFocusRing");
var $75P61$reactariauseListFormatter = require("react-aria/useListFormatter");
var $75P61$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SelectContext", function () { return $4080fc4198ecec34$export$7540cee5be7dc19b; });
$parcel$export(module.exports, "SelectStateContext", function () { return $4080fc4198ecec34$export$ef445b55be0601bd; });
$parcel$export(module.exports, "Select", function () { return $4080fc4198ecec34$export$ef9b1a59e592288f; });
$parcel$export(module.exports, "SelectValueContext", function () { return $4080fc4198ecec34$export$f8f745c04421623f; });
$parcel$export(module.exports, "SelectValue", function () { return $4080fc4198ecec34$export$e288731fd71264f0; });
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



















const $4080fc4198ecec34$export$7540cee5be7dc19b = /*#__PURE__*/ (0, $75P61$react.createContext)(null);
const $4080fc4198ecec34$export$ef445b55be0601bd = /*#__PURE__*/ (0, $75P61$react.createContext)(null);
const $4080fc4198ecec34$export$ef9b1a59e592288f = /*#__PURE__*/ (0, $75P61$reactariaprivatecollectionsHidden.createHideableComponent)(function Select(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $4080fc4198ecec34$export$7540cee5be7dc19b);
    let { children: children, isDisabled: isDisabled = false, isInvalid: isInvalid = false, isRequired: isRequired = false } = props;
    let content = (0, $75P61$react.useMemo)(()=>typeof children === 'function' ? children({
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
    return /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $75P61$reactariaCollectionBuilder.CollectionBuilder), {
        content: content
    }, (collection)=>/*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement($4080fc4198ecec34$var$SelectInner, {
            props: props,
            collection: collection,
            selectRef: ref
        }));
});
// Contexts to clear inside the popover.
const $4080fc4198ecec34$var$CLEAR_CONTEXTS = [
    (0, $d5d46822336ca1e1$exports.LabelContext),
    (0, $16c7f9b22cce3838$exports.ButtonContext),
    (0, $cab7d9a238d19c33$exports.TextContext)
];
function $4080fc4198ecec34$var$SelectInner({ props: props, selectRef: ref, collection: collection }) {
    let { validationBehavior: formValidationBehavior } = (0, $048d76b84370f141$exports.useSlottedContext)((0, $5adc12e2ce73be8f$exports.FormContext)) || {};
    let validationBehavior = props.validationBehavior ?? formValidationBehavior ?? 'native';
    let state = (0, $75P61$reactstatelyuseSelectState.useSelectState)({
        ...props,
        collection: collection,
        children: undefined,
        validationBehavior: validationBehavior
    });
    let { isFocusVisible: isFocusVisible, focusProps: focusProps } = (0, $75P61$reactariauseFocusRing.useFocusRing)({
        within: true
    });
    // Get props for child elements from useSelect
    let buttonRef = (0, $75P61$react.useRef)(null);
    let [labelRef, label] = (0, $048d76b84370f141$exports.useSlot)(!props['aria-label'] && !props['aria-labelledby']);
    let { labelProps: labelProps, triggerProps: triggerProps, valueProps: valueProps, menuProps: menuProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, hiddenSelectProps: hiddenSelectProps, ...validation } = (0, $75P61$reactariauseSelect.useSelect)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        label: label,
        validationBehavior: validationBehavior
    }, state, buttonRef);
    // Only expose a subset of state to renderProps function to avoid infinite render loop
    let renderPropsState = (0, $75P61$react.useMemo)(()=>({
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
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: renderPropsState,
        defaultClassName: 'react-aria-Select'
    });
    let DOMProps = (0, $75P61$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    let scrollRef = (0, $75P61$react.useRef)(null);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $4080fc4198ecec34$export$7540cee5be7dc19b,
                props
            ],
            [
                $4080fc4198ecec34$export$ef445b55be0601bd,
                state
            ],
            [
                $4080fc4198ecec34$export$f8f745c04421623f,
                valueProps
            ],
            [
                (0, $d5d46822336ca1e1$exports.LabelContext),
                {
                    ...labelProps,
                    ref: labelRef,
                    elementType: 'span'
                }
            ],
            [
                (0, $16c7f9b22cce3838$exports.ButtonContext),
                {
                    ...triggerProps,
                    ref: buttonRef,
                    isPressed: state.isOpen,
                    autoFocus: props.autoFocus
                }
            ],
            [
                (0, $88595bf043e542ec$exports.OverlayTriggerStateContext),
                state
            ],
            [
                (0, $74e35a768d38d46b$exports.PopoverContext),
                {
                    trigger: 'Select',
                    triggerRef: buttonRef,
                    scrollRef: scrollRef,
                    placement: 'bottom start',
                    'aria-labelledby': menuProps['aria-labelledby'],
                    clearContexts: $4080fc4198ecec34$var$CLEAR_CONTEXTS
                }
            ],
            [
                (0, $537333b300f7e667$exports.ListBoxContext),
                {
                    ...menuProps,
                    ref: scrollRef
                }
            ],
            [
                (0, $537333b300f7e667$exports.ListStateContext),
                state
            ],
            [
                (0, $cab7d9a238d19c33$exports.TextContext),
                {
                    slots: {
                        description: descriptionProps,
                        errorMessage: errorMessageProps
                    }
                }
            ],
            [
                (0, $862aa7df04d8fa76$exports.FieldErrorContext),
                validation
            ]
        ]
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $75P61$reactariamergeProps.mergeProps)(DOMProps, renderProps, focusProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-focused": state.isFocused || undefined,
        "data-focus-visible": isFocusVisible || undefined,
        "data-open": state.isOpen || undefined,
        "data-disabled": props.isDisabled || undefined,
        "data-invalid": validation.isInvalid || undefined,
        "data-required": props.isRequired || undefined
    }, renderProps.children, /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $75P61$reactariauseSelect.HiddenSelect), {
        ...hiddenSelectProps,
        autoComplete: props.autoComplete
    })));
}
const $4080fc4198ecec34$export$f8f745c04421623f = /*#__PURE__*/ (0, $75P61$react.createContext)(null);
const $4080fc4198ecec34$export$e288731fd71264f0 = /*#__PURE__*/ (0, $75P61$reactariaprivatecollectionsHidden.createHideableComponent)(function SelectValue(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $4080fc4198ecec34$export$f8f745c04421623f);
    let state = (0, $75P61$react.useContext)($4080fc4198ecec34$export$ef445b55be0601bd);
    let { placeholder: placeholder } = (0, $048d76b84370f141$exports.useSlottedContext)($4080fc4198ecec34$export$7540cee5be7dc19b);
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
    let formatter = (0, $75P61$reactariauseListFormatter.useListFormatter)();
    let textValue = (0, $75P61$react.useMemo)(()=>state.selectedItems.map((item)=>item?.textValue), [
        state.selectedItems
    ]);
    let selectionMode = state.selectionManager.selectionMode;
    let selectedText = (0, $75P61$react.useMemo)(()=>selectionMode === 'single' ? textValue[0] ?? '' : formatter.format(textValue), [
        selectionMode,
        formatter,
        textValue
    ]);
    let defaultChildren = (0, $75P61$react.useMemo)(()=>{
        if (selectionMode === 'single') return rendered[0];
        let parts = formatter.formatToParts(textValue);
        if (parts.length === 0) return null;
        let index = 0;
        return parts.map((part)=>{
            if (part.type === 'element') return /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $75P61$react.Fragment), {
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
    let stringFormatter = (0, $75P61$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($5724b511a2687756$exports))), 'react-aria-components');
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        defaultChildren: defaultChildren ?? placeholder ?? stringFormatter.format('selectPlaceholder'),
        defaultClassName: 'react-aria-SelectValue',
        values: {
            selectedItem: state.selectedItems[0]?.value ?? null,
            selectedItems: (0, $75P61$react.useMemo)(()=>state.selectedItems.map((item)=>item.value ?? null), [
                state.selectedItems
            ]),
            selectedText: selectedText,
            isPlaceholder: state.selectedItems.length === 0,
            state: state
        }
    });
    let DOMProps = (0, $75P61$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $048d76b84370f141$exports.dom).span, {
        ref: ref,
        ...DOMProps,
        ...renderProps,
        "data-placeholder": state.selectedItems.length === 0 || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($75P61$react))).createElement((0, $cab7d9a238d19c33$exports.TextContext).Provider, {
        value: undefined
    }, renderProps.children));
});


//# sourceMappingURL=Select.cjs.map
