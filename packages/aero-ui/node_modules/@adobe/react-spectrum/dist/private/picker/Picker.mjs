import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {dimensionValue as $63d03c54ca5e4b88$export$abc24f5b99744ea6} from "../utils/styleProps.mjs";
import {Field as $adcd096854d27620$export$a455218a85c89869} from "../label/Field.mjs";
import {FieldButton as $9b445aa2bd8cce4c$export$47dc48f595b075da} from "../button/FieldButton.mjs";
import $4ZBLB$intlStringsmjs from "./intlStrings.mjs";
import {ListBoxBase as $ee13b4eccaed924f$export$1afdcf349979fb7e, useListBoxLayout as $ee13b4eccaed924f$export$25768ea656ae32a7} from "../listbox/ListBoxBase.mjs";
import {Popover as $3a473e3b7032f626$export$5b6b19405a83ff9d} from "../overlays/Popover.mjs";
import {ProgressCircle as $1cfe37e7feefa23d$export$c79b9d6b4cc92af7} from "../progress/ProgressCircle.mjs";
import {SlotProvider as $62024859ff9f1f8a$export$8107b24b91795686, useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import "../dropdown_vars.css";
import $4ZBLB$dropdown_vars_cssmjs from "../dropdown_vars_css.mjs";
import {Text as $f8cc90fea9436c19$export$5f1af8db9871e1d6} from "../text/Text.mjs";
import {Tray as $9fca089dca5508dc$export$4589ed81930b555c} from "../overlays/Tray.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8, useUnwrapDOMRef as $3c2c983d5210446c$export$1d5cc31d9d8df817} from "../utils/useDOMRef.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useIsMobileDevice as $f357d4aae54bf1ff$export$736bf165441b18c7} from "../utils/useIsMobileDevice.mjs";
import {useProvider as $71dfb0e0358a12de$export$693cdb10cec23617, useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import $4ZBLB$spectrumiconsuiAlertMedium from "@spectrum-icons/ui/AlertMedium";
import {useSelect as $4ZBLB$useSelect, HiddenSelect as $4ZBLB$HiddenSelect} from "react-aria/useSelect";
import $4ZBLB$spectrumiconsuiChevronDownMedium from "@spectrum-icons/ui/ChevronDownMedium";
import {mergeProps as $4ZBLB$mergeProps} from "react-aria/mergeProps";
import {PressResponder as $4ZBLB$PressResponder} from "react-aria/private/interactions/PressResponder";
import $4ZBLB$react, {useRef as $4ZBLB$useRef, useState as $4ZBLB$useState, useCallback as $4ZBLB$useCallback} from "react";
import {useHover as $4ZBLB$useHover} from "react-aria/useHover";
import {useId as $4ZBLB$useId} from "react-aria/useId";
import {useLayoutEffect as $4ZBLB$useLayoutEffect} from "react-aria/private/utils/useLayoutEffect";
import {useLocalizedStringFormatter as $4ZBLB$useLocalizedStringFormatter} from "react-aria/useLocalizedStringFormatter";
import {useResizeObserver as $4ZBLB$useResizeObserver} from "react-aria/private/utils/useResizeObserver";
import {useSelectState as $4ZBLB$useSelectState} from "react-stately/useSelectState";


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



























const $933e5a05c989c3a1$export$ba25329847403e11 = /*#__PURE__*/ (0, $4ZBLB$react).forwardRef(function Picker(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'picker');
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let stringFormatter = (0, $4ZBLB$useLocalizedStringFormatter)((0, ($parcel$interopDefault($4ZBLB$intlStringsmjs))), '@react-spectrum/picker');
    let { autoComplete: autoComplete, isDisabled: isDisabled, direction: direction = 'bottom', align: align = 'start', shouldFlip: shouldFlip = true, placeholder: placeholder = stringFormatter.format('placeholder'), isQuiet: isQuiet, labelPosition: labelPosition = 'top', menuWidth: menuWidth, autoFocus: autoFocus } = props;
    let state = (0, $4ZBLB$useSelectState)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let popoverRef = (0, $4ZBLB$useRef)(null);
    let triggerRef = (0, $4ZBLB$useRef)(null);
    let unwrappedTriggerRef = (0, $3c2c983d5210446c$export$1d5cc31d9d8df817)(triggerRef);
    let listboxRef = (0, $4ZBLB$useRef)(null);
    let isLoadingInitial = props.isLoading && state.collection.size === 0;
    let isLoadingMore = props.isLoading && state.collection.size > 0;
    let progressCircleId = (0, $4ZBLB$useId)();
    // We create the listbox layout in Picker and pass it to ListBoxBase below
    // so that the layout information can be cached even while the listbox is not mounted.
    // We also use the layout as the keyboard delegate for type to select.
    let layout = (0, $ee13b4eccaed924f$export$25768ea656ae32a7)();
    let { labelProps: labelProps, triggerProps: triggerProps, valueProps: valueProps, menuProps: menuProps, hiddenSelectProps: hiddenSelectProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $4ZBLB$useSelect)({
        ...props,
        'aria-describedby': isLoadingInitial ? progressCircleId : undefined
    }, state, unwrappedTriggerRef);
    let isMobile = (0, $f357d4aae54bf1ff$export$736bf165441b18c7)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $4ZBLB$useHover)({
        isDisabled: isDisabled
    });
    // On small screen devices, the listbox is rendered in a tray, otherwise a popover.
    let listbox = /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $ee13b4eccaed924f$export$1afdcf349979fb7e), {
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
    let [buttonWidth, setButtonWidth] = (0, $4ZBLB$useState)(undefined);
    let { scale: scale } = (0, $71dfb0e0358a12de$export$693cdb10cec23617)();
    let onResize = (0, $4ZBLB$useCallback)(()=>{
        if (!isMobile && unwrappedTriggerRef.current) {
            let width = unwrappedTriggerRef.current.offsetWidth;
            setButtonWidth(width);
        }
    }, [
        unwrappedTriggerRef,
        setButtonWidth,
        isMobile
    ]);
    (0, $4ZBLB$useResizeObserver)({
        ref: unwrappedTriggerRef,
        onResize: onResize
    });
    (0, $4ZBLB$useLayoutEffect)(onResize, [
        scale,
        state.selectedKey,
        onResize
    ]);
    let overlay;
    if (isMobile) overlay = /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $9fca089dca5508dc$export$4589ed81930b555c), {
        state: state
    }, listbox);
    else {
        // If quiet, use the default width, otherwise match the width of the button. This can be overridden by the menuWidth prop.
        // Always have a minimum width of the button width. When quiet, there is an extra offset to add.
        // Not using style props for this because they don't support `calc`.
        let width = isQuiet ? undefined : buttonWidth;
        let style = {
            width: menuWidth ? (0, $63d03c54ca5e4b88$export$abc24f5b99744ea6)(menuWidth) : width,
            minWidth: isQuiet ? `calc(${buttonWidth}px + calc(2 * var(--spectrum-dropdown-quiet-offset)))` : buttonWidth
        };
        overlay = /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $3a473e3b7032f626$export$5b6b19405a83ff9d), {
            UNSAFE_style: style,
            UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-popover', {
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
    if (typeof contents === 'string') contents = /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $f8cc90fea9436c19$export$5f1af8db9871e1d6), null, contents);
    let picker = /*#__PURE__*/ (0, $4ZBLB$react).createElement("div", {
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown', {
            'is-invalid': isInvalid && !isDisabled,
            'is-disabled': isDisabled,
            'spectrum-Dropdown--quiet': isQuiet
        })
    }, /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $4ZBLB$HiddenSelect), {
        autoComplete: autoComplete,
        ...hiddenSelectProps
    }), /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $4ZBLB$PressResponder), (0, $4ZBLB$mergeProps)(hoverProps, triggerProps), /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $9b445aa2bd8cce4c$export$47dc48f595b075da), {
        ref: triggerRef,
        isActive: state.isOpen,
        isQuiet: isQuiet,
        isDisabled: isDisabled,
        isInvalid: isInvalid,
        autoFocus: autoFocus,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-trigger', {
            'is-hovered': isHovered
        })
    }, /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $62024859ff9f1f8a$export$8107b24b91795686), {
        slots: {
            icon: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Icon'),
                size: 'S'
            },
            avatar: {
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-avatar'),
                size: 'avatar-size-100'
            },
            text: {
                ...valueProps,
                UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-label', {
                    'is-placeholder': !state.selectedItem
                })
            },
            description: {
                isHidden: true
            }
        }
    }, contents), isLoadingInitial && /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $1cfe37e7feefa23d$export$c79b9d6b4cc92af7), {
        id: progressCircleId,
        isIndeterminate: true,
        size: "S",
        "aria-label": stringFormatter.format('loading'),
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-progressCircle')
    }), isInvalid && !isLoadingInitial && !isDisabled && /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $4ZBLB$spectrumiconsuiAlertMedium), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-invalidIcon')
    }), /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $4ZBLB$spectrumiconsuiChevronDownMedium), {
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Dropdown-chevron')
    }))), state.collection.size === 0 ? null : overlay);
    let wrapperClassName = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($4ZBLB$dropdown_vars_cssmjs))), 'spectrum-Field', {
        'spectrum-Dropdown-fieldWrapper--quiet': isQuiet,
        'spectrum-Dropdown-fieldWrapper--positionSide': labelPosition === 'side'
    });
    return /*#__PURE__*/ (0, $4ZBLB$react).createElement((0, $adcd096854d27620$export$a455218a85c89869), {
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


export {$933e5a05c989c3a1$export$ba25329847403e11 as Picker};
//# sourceMappingURL=Picker.mjs.map
