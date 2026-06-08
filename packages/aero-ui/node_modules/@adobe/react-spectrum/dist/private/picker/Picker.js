import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {dimensionValue as $120fbea2d95e11ed$export$abc24f5b99744ea6} from "../utils/styleProps.js";
import {Field as $3967792f95357356$export$a455218a85c89869} from "../label/Field.js";
import {FieldButton as $1fa99bd0fd8b0a92$export$47dc48f595b075da} from "../button/FieldButton.js";
import $i9Pjf$intlStringsjs from "./intlStrings.js";
import {ListBoxBase as $45f8932a4e549cb6$export$1afdcf349979fb7e, useListBoxLayout as $45f8932a4e549cb6$export$25768ea656ae32a7} from "../listbox/ListBoxBase.js";
import {Popover as $2fa1c97e743ad66b$export$5b6b19405a83ff9d} from "../overlays/Popover.js";
import {ProgressCircle as $277696409c391eff$export$c79b9d6b4cc92af7} from "../progress/ProgressCircle.js";
import {SlotProvider as $68f4bc2c1abc5618$export$8107b24b91795686, useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import "../dropdown_vars.css";
import $i9Pjf$dropdown_vars_cssmjs from "../dropdown_vars_css.mjs";
import {Text as $42dd7396e689e4e6$export$5f1af8db9871e1d6} from "../text/Text.js";
import {Tray as $16b239851776d94c$export$4589ed81930b555c} from "../overlays/Tray.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8, useUnwrapDOMRef as $c234463e9ef56637$export$1d5cc31d9d8df817} from "../utils/useDOMRef.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useIsMobileDevice as $196ab9279fe71c29$export$736bf165441b18c7} from "../utils/useIsMobileDevice.js";
import {useProvider as $089943c7a219141c$export$693cdb10cec23617, useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import $i9Pjf$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import {useSelect as $i9Pjf$useSelect, HiddenSelect as $i9Pjf$HiddenSelect} from "react-aria/useSelect";
import $i9Pjf$spectrumiconsuiChevronDownMedium from "@spectrum-icons/ui/ChevronDownMedium";
import {mergeProps as $i9Pjf$mergeProps} from "react-aria/mergeProps";
import {PressResponder as $i9Pjf$PressResponder} from "react-aria/private/interactions/PressResponder";
import $i9Pjf$react, {useRef as $i9Pjf$useRef, useState as $i9Pjf$useState, useCallback as $i9Pjf$useCallback} from "react";
import {useHover as $i9Pjf$useHover} from "react-aria/useHover";
import {useId as $i9Pjf$useId} from "react-aria/useId";
import {useLayoutEffect as $i9Pjf$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useLocalizedStringFormatter as $i9Pjf$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useResizeObserver as $i9Pjf$useResizeObserver} from "react-aria/private/utils/useResizeObserver";
import {useSelectState as $i9Pjf$useSelectState} from "react-stately/useSelectState";


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



























const $fcdeb62019c30c53$export$ba25329847403e11 = /*#__PURE__*/ (0, $i9Pjf$react).forwardRef(function Picker(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'picker');
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let stringFormatter = (0, $i9Pjf$useLocalizedStringFormatter)((0, ($parcel$interopDefault($i9Pjf$intlStringsjs))), '@react-spectrum/picker');
    let { autoComplete: autoComplete, isDisabled: isDisabled, direction: direction = 'bottom', align: align = 'start', shouldFlip: shouldFlip = true, placeholder: placeholder = stringFormatter.format('placeholder'), isQuiet: isQuiet, labelPosition: labelPosition = 'top', menuWidth: menuWidth, autoFocus: autoFocus } = props;
    let state = (0, $i9Pjf$useSelectState)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let popoverRef = (0, $i9Pjf$useRef)(null);
    let triggerRef = (0, $i9Pjf$useRef)(null);
    let unwrappedTriggerRef = (0, $c234463e9ef56637$export$1d5cc31d9d8df817)(triggerRef);
    let listboxRef = (0, $i9Pjf$useRef)(null);
    let isLoadingInitial = props.isLoading && state.collection.size === 0;
    let isLoadingMore = props.isLoading && state.collection.size > 0;
    let progressCircleId = (0, $i9Pjf$useId)();
    // We create the listbox layout in Picker and pass it to ListBoxBase below
    // so that the layout information can be cached even while the listbox is not mounted.
    // We also use the layout as the keyboard delegate for type to select.
    let layout = (0, $45f8932a4e549cb6$export$25768ea656ae32a7)();
    let { labelProps: labelProps, triggerProps: triggerProps, valueProps: valueProps, menuProps: menuProps, hiddenSelectProps: hiddenSelectProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $i9Pjf$useSelect)({
        ...props,
        'aria-describedby': isLoadingInitial ? progressCircleId : undefined
    }, state, unwrappedTriggerRef);
    let isMobile = (0, $196ab9279fe71c29$export$736bf165441b18c7)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $i9Pjf$useHover)({
        isDisabled: isDisabled
    });
    // On small screen devices, the listbox is rendered in a tray, otherwise a popover.
    let listbox = /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $45f8932a4e549cb6$export$1afdcf349979fb7e), {
        ...menuProps,
        ref: listboxRef,
        disallowEmptySelection: true,
        autoFocus: state.focusStrategy || true,
        shouldSelectOnPressUp: true,
        focusOnPointerEnter: true,
        layout: layout,
        state: state,
        width: isMobile ? '100%' : undefined,
        // Set max height: inherit so Tray scrolling works
        UNSAFE_style: {
            maxHeight: 'inherit'
        },
        isLoading: props.isLoading,
        showLoadingSpinner: isLoadingMore,
        onLoadMore: props.onLoadMore
    });
    // Measure the width of the button to inform the width of the menu (below).
    let [buttonWidth, setButtonWidth] = (0, $i9Pjf$useState)(undefined);
    let { scale: scale } = (0, $089943c7a219141c$export$693cdb10cec23617)();
    let onResize = (0, $i9Pjf$useCallback)(()=>{
        if (!isMobile && unwrappedTriggerRef.current) {
            let width = unwrappedTriggerRef.current.offsetWidth;
            setButtonWidth(width);
        }
    }, [
        unwrappedTriggerRef,
        setButtonWidth,
        isMobile
    ]);
    (0, $i9Pjf$useResizeObserver)({
        ref: unwrappedTriggerRef,
        onResize: onResize
    });
    (0, $i9Pjf$useLayoutEffect)(onResize, [
        scale,
        state.selectedKey,
        onResize
    ]);
    let overlay;
    if (isMobile) overlay = /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $16b239851776d94c$export$4589ed81930b555c), {
        state: state
    }, listbox);
    else {
        // If quiet, use the default width, otherwise match the width of the button. This can be overridden by the menuWidth prop.
        // Always have a minimum width of the button width. When quiet, there is an extra offset to add.
        // Not using style props for this because they don't support `calc`.
        let width = isQuiet ? undefined : buttonWidth;
        let style = {
            width: menuWidth ? (0, $120fbea2d95e11ed$export$abc24f5b99744ea6)(menuWidth) : width,
            minWidth: isQuiet ? `calc(${buttonWidth}px + calc(2 * var(--spectrum-dropdown-quiet-offset)))` : buttonWidth
        };
        overlay = /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $2fa1c97e743ad66b$export$5b6b19405a83ff9d), {
            UNSAFE_style: style,
            UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-popover', {
                'spectrum-Dropdown-popover--quiet': isQuiet
            }),
            ref: popoverRef,
            placement: `${direction} ${align}`,
            shouldFlip: shouldFlip,
            hideArrow: true,
            state: state,
            triggerRef: unwrappedTriggerRef,
            scrollRef: listboxRef,
            shouldContainFocus: true
        }, listbox);
    }
    let contents = state.selectedItem ? state.selectedItem.rendered : placeholder;
    if (typeof contents === 'string') contents = /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $42dd7396e689e4e6$export$5f1af8db9871e1d6), null, contents);
    let picker = /*#__PURE__*/ (0, $i9Pjf$react).createElement("div", {
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown', {
            'is-invalid': isInvalid && !isDisabled,
            'is-disabled': isDisabled,
            'spectrum-Dropdown--quiet': isQuiet
        })
    }, /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $i9Pjf$HiddenSelect), {
        autoComplete: autoComplete,
        ...hiddenSelectProps
    }), /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $i9Pjf$PressResponder), (0, $i9Pjf$mergeProps)(hoverProps, triggerProps), /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $1fa99bd0fd8b0a92$export$47dc48f595b075da), {
        ref: triggerRef,
        isActive: state.isOpen,
        isQuiet: isQuiet,
        isDisabled: isDisabled,
        isInvalid: isInvalid,
        autoFocus: autoFocus,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-trigger', {
            'is-hovered': isHovered
        })
    }, /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $68f4bc2c1abc5618$export$8107b24b91795686), {
        slots: {
            icon: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Icon'),
                size: 'S'
            },
            avatar: {
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-avatar'),
                size: 'avatar-size-100'
            },
            text: {
                ...valueProps,
                UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-label', {
                    'is-placeholder': !state.selectedItem
                })
            },
            description: {
                isHidden: true
            }
        }
    }, contents), isLoadingInitial && /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $277696409c391eff$export$c79b9d6b4cc92af7), {
        id: progressCircleId,
        isIndeterminate: true,
        size: "S",
        "aria-label": stringFormatter.format('loading'),
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-progressCircle')
    }), isInvalid && !isLoadingInitial && !isDisabled && /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $i9Pjf$spectrumiconsuiAlertMedium), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-invalidIcon')
    }), /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $i9Pjf$spectrumiconsuiChevronDownMedium), {
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Dropdown-chevron')
    }))), state.collection.size === 0 ? null : overlay);
    let wrapperClassName = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($i9Pjf$dropdown_vars_cssmjs))), 'spectrum-Field', {
        'spectrum-Dropdown-fieldWrapper--quiet': isQuiet,
        'spectrum-Dropdown-fieldWrapper--positionSide': labelPosition === 'side'
    });
    return /*#__PURE__*/ (0, $i9Pjf$react).createElement((0, $3967792f95357356$export$a455218a85c89869), {
        ...props,
        ref: domRef,
        wrapperClassName: wrapperClassName,
        labelProps: labelProps,
        descriptionProps: descriptionProps,
        errorMessageProps: errorMessageProps,
        isInvalid: isInvalid,
        validationErrors: validationErrors,
        validationDetails: validationDetails,
        showErrorIcon: false,
        includeNecessityIndicatorInAccessibilityName: true,
        elementType: "span"
    }, picker);
});


export {$fcdeb62019c30c53$export$ba25329847403e11 as Picker};
//# sourceMappingURL=Picker.js.map
