var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $b93966d678e0af07$exports = require("../label/Field.cjs");
var $23798a2a76e33abb$exports = require("../button/FieldButton.cjs");
var $4b4d3f8de12e4118$exports = require("./intlStrings.cjs");
var $cb7ee1d9d5613db9$exports = require("../listbox/ListBoxBase.cjs");
var $39ed1c805b59752f$exports = require("../overlays/Popover.cjs");
var $948c2416aa3a9507$exports = require("../progress/ProgressCircle.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
require("../dropdown_vars.css");
var $72a2c6b6cc4f810f$exports = require("../dropdown_vars_css.cjs");
var $15e3b68ec42125a9$exports = require("../text/Text.cjs");
var $378dee1409fe2937$exports = require("../overlays/Tray.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $1af2ca8553741739$exports = require("../form/Form.cjs");
var $0b97cdf6ccc1e502$exports = require("../utils/useIsMobileDevice.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $aRfxF$spectrumiconsuiAlertMedium = require("@spectrum-icons/ui/AlertMedium");
var $aRfxF$reactariauseSelect = require("react-aria/useSelect");
var $aRfxF$spectrumiconsuiChevronDownMedium = require("@spectrum-icons/ui/ChevronDownMedium");
var $aRfxF$reactariamergeProps = require("react-aria/mergeProps");
var $aRfxF$reactariaprivateinteractionsPressResponder = require("react-aria/private/interactions/PressResponder");
var $aRfxF$react = require("react");
var $aRfxF$reactariauseHover = require("react-aria/useHover");
var $aRfxF$reactariauseId = require("react-aria/useId");
var $aRfxF$reactariaprivateutilsuseLayoutEffect = require("react-aria/private/utils/useLayoutEffect");
var $aRfxF$reactariauseLocalizedStringFormatter = require("react-aria/useLocalizedStringFormatter");
var $aRfxF$reactariaprivateutilsuseResizeObserver = require("react-aria/private/utils/useResizeObserver");
var $aRfxF$reactstatelyuseSelectState = require("react-stately/useSelectState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Picker", function () { return $4ab2867caa392e8e$export$ba25329847403e11; });
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



























const $4ab2867caa392e8e$export$ba25329847403e11 = /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).forwardRef(function Picker(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'picker');
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $1af2ca8553741739$exports.useFormProps)(props);
    let stringFormatter = (0, $aRfxF$reactariauseLocalizedStringFormatter.useLocalizedStringFormatter)((0, ($parcel$interopDefault($4b4d3f8de12e4118$exports))), '@react-spectrum/picker');
    let { autoComplete: autoComplete, isDisabled: isDisabled, direction: direction = 'bottom', align: align = 'start', shouldFlip: shouldFlip = true, placeholder: placeholder = stringFormatter.format('placeholder'), isQuiet: isQuiet, labelPosition: labelPosition = 'top', menuWidth: menuWidth, autoFocus: autoFocus } = props;
    let state = (0, $aRfxF$reactstatelyuseSelectState.useSelectState)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let popoverRef = (0, $aRfxF$react.useRef)(null);
    let triggerRef = (0, $aRfxF$react.useRef)(null);
    let unwrappedTriggerRef = (0, $65aea7b37663976b$exports.useUnwrapDOMRef)(triggerRef);
    let listboxRef = (0, $aRfxF$react.useRef)(null);
    let isLoadingInitial = props.isLoading && state.collection.size === 0;
    let isLoadingMore = props.isLoading && state.collection.size > 0;
    let progressCircleId = (0, $aRfxF$reactariauseId.useId)();
    // We create the listbox layout in Picker and pass it to ListBoxBase below
    // so that the layout information can be cached even while the listbox is not mounted.
    // We also use the layout as the keyboard delegate for type to select.
    let layout = (0, $cb7ee1d9d5613db9$exports.useListBoxLayout)();
    let { labelProps: labelProps, triggerProps: triggerProps, valueProps: valueProps, menuProps: menuProps, hiddenSelectProps: hiddenSelectProps, descriptionProps: descriptionProps, errorMessageProps: errorMessageProps, isInvalid: isInvalid, validationErrors: validationErrors, validationDetails: validationDetails } = (0, $aRfxF$reactariauseSelect.useSelect)({
        ...props,
        'aria-describedby': isLoadingInitial ? progressCircleId : undefined
    }, state, unwrappedTriggerRef);
    let isMobile = (0, $0b97cdf6ccc1e502$exports.useIsMobileDevice)();
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $aRfxF$reactariauseHover.useHover)({
        isDisabled: isDisabled
    });
    // On small screen devices, the listbox is rendered in a tray, otherwise a popover.
    let listbox = /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $cb7ee1d9d5613db9$exports.ListBoxBase), {
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
    let [buttonWidth, setButtonWidth] = (0, $aRfxF$react.useState)(undefined);
    let { scale: scale } = (0, $544fc82701fc93e9$exports.useProvider)();
    let onResize = (0, $aRfxF$react.useCallback)(()=>{
        if (!isMobile && unwrappedTriggerRef.current) {
            let width = unwrappedTriggerRef.current.offsetWidth;
            setButtonWidth(width);
        }
    }, [
        unwrappedTriggerRef,
        setButtonWidth,
        isMobile
    ]);
    (0, $aRfxF$reactariaprivateutilsuseResizeObserver.useResizeObserver)({
        ref: unwrappedTriggerRef,
        onResize: onResize
    });
    (0, $aRfxF$reactariaprivateutilsuseLayoutEffect.useLayoutEffect)(onResize, [
        scale,
        state.selectedKey,
        onResize
    ]);
    let overlay;
    if (isMobile) overlay = /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $378dee1409fe2937$exports.Tray), {
        state: state
    }, listbox);
    else {
        // If quiet, use the default width, otherwise match the width of the button. This can be overridden by the menuWidth prop.
        // Always have a minimum width of the button width. When quiet, there is an extra offset to add.
        // Not using style props for this because they don't support `calc`.
        let width = isQuiet ? undefined : buttonWidth;
        let style = {
            width: menuWidth ? (0, $b8f90d51c4908137$exports.dimensionValue)(menuWidth) : width,
            minWidth: isQuiet ? `calc(${buttonWidth}px + calc(2 * var(--spectrum-dropdown-quiet-offset)))` : buttonWidth
        };
        overlay = /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $39ed1c805b59752f$exports.Popover), {
            UNSAFE_style: style,
            UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-popover', {
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
    if (typeof contents === 'string') contents = /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $15e3b68ec42125a9$exports.Text), null, contents);
    let picker = /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement("div", {
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown', {
            'is-invalid': isInvalid && !isDisabled,
            'is-disabled': isDisabled,
            'spectrum-Dropdown--quiet': isQuiet
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $aRfxF$reactariauseSelect.HiddenSelect), {
        autoComplete: autoComplete,
        ...hiddenSelectProps
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $aRfxF$reactariaprivateinteractionsPressResponder.PressResponder), (0, $aRfxF$reactariamergeProps.mergeProps)(hoverProps, triggerProps), /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $23798a2a76e33abb$exports.FieldButton), {
        ref: triggerRef,
        isActive: state.isOpen,
        isQuiet: isQuiet,
        isDisabled: isDisabled,
        isInvalid: isInvalid,
        autoFocus: autoFocus,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-trigger', {
            'is-hovered': isHovered
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $feede71cddc0c5f3$exports.SlotProvider), {
        slots: {
            icon: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Icon'),
                size: 'S'
            },
            avatar: {
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-avatar'),
                size: 'avatar-size-100'
            },
            text: {
                ...valueProps,
                UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-label', {
                    'is-placeholder': !state.selectedItem
                })
            },
            description: {
                isHidden: true
            }
        }
    }, contents), isLoadingInitial && /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $948c2416aa3a9507$exports.ProgressCircle), {
        id: progressCircleId,
        isIndeterminate: true,
        size: "S",
        "aria-label": stringFormatter.format('loading'),
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-progressCircle')
    }), isInvalid && !isLoadingInitial && !isDisabled && /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, ($parcel$interopDefault($aRfxF$spectrumiconsuiAlertMedium))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-invalidIcon')
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, ($parcel$interopDefault($aRfxF$spectrumiconsuiChevronDownMedium))), {
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Dropdown-chevron')
    }))), state.collection.size === 0 ? null : overlay);
    let wrapperClassName = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($72a2c6b6cc4f810f$exports))), 'spectrum-Field', {
        'spectrum-Dropdown-fieldWrapper--quiet': isQuiet,
        'spectrum-Dropdown-fieldWrapper--positionSide': labelPosition === 'side'
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($aRfxF$react))).createElement((0, $b93966d678e0af07$exports.Field), {
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


//# sourceMappingURL=Picker.cjs.map
