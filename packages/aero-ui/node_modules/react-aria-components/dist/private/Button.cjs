var $048d76b84370f141$exports = require("./utils.cjs");
var $ffc270943183f850$exports = require("./ProgressBar.cjs");
var $hsYW5$reactariaprivateliveannouncerLiveAnnouncer = require("react-aria/private/live-announcer/LiveAnnouncer");
var $hsYW5$reactariauseButton = require("react-aria/useButton");
var $hsYW5$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $hsYW5$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $hsYW5$reactariamergeProps = require("react-aria/mergeProps");
var $hsYW5$react = require("react");
var $hsYW5$reactariauseFocusRing = require("react-aria/useFocusRing");
var $hsYW5$reactariauseHover = require("react-aria/useHover");
var $hsYW5$reactariauseId = require("react-aria/useId");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ButtonContext", function () { return $16c7f9b22cce3838$export$24d547caef80ccd1; });
$parcel$export(module.exports, "Button", function () { return $16c7f9b22cce3838$export$353f5b6fc5456de1; });
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










const $16c7f9b22cce3838$export$24d547caef80ccd1 = /*#__PURE__*/ (0, $hsYW5$react.createContext)({});
const $16c7f9b22cce3838$export$353f5b6fc5456de1 = /*#__PURE__*/ (0, $hsYW5$reactariaprivatecollectionsHidden.createHideableComponent)(function Button(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $16c7f9b22cce3838$export$24d547caef80ccd1);
    let ctx = props;
    let { isPending: isPending } = ctx;
    let { buttonProps: buttonProps, isPressed: isPressed } = (0, $hsYW5$reactariauseButton.useButton)(props, ref);
    buttonProps = $16c7f9b22cce3838$var$useDisableInteractions(buttonProps, isPending);
    let { focusProps: focusProps, isFocused: isFocused, isFocusVisible: isFocusVisible } = (0, $hsYW5$reactariauseFocusRing.useFocusRing)(props);
    let { hoverProps: hoverProps, isHovered: isHovered } = (0, $hsYW5$reactariauseHover.useHover)({
        ...props,
        isDisabled: props.isDisabled || isPending
    });
    let renderValues = {
        isHovered: isHovered,
        isPressed: (ctx.isPressed || isPressed) && !isPending,
        isFocused: isFocused,
        isFocusVisible: isFocusVisible,
        isDisabled: props.isDisabled || false,
        isPending: isPending ?? false
    };
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: renderValues,
        defaultClassName: 'react-aria-Button'
    });
    let buttonId = (0, $hsYW5$reactariauseId.useId)(buttonProps.id);
    let progressId = (0, $hsYW5$reactariauseId.useId)();
    let ariaLabelledby = buttonProps['aria-labelledby'];
    if (isPending) {
        // aria-labelledby wins over aria-label
        // https://www.w3.org/TR/accname-1.2/#computation-steps
        if (ariaLabelledby) ariaLabelledby = `${ariaLabelledby} ${progressId}`;
        else if (buttonProps['aria-label']) ariaLabelledby = `${buttonId} ${progressId}`;
    }
    let wasPending = (0, $hsYW5$react.useRef)(isPending);
    (0, $hsYW5$react.useEffect)(()=>{
        let message = {
            'aria-labelledby': ariaLabelledby || buttonId
        };
        if (!wasPending.current && isFocused && isPending) (0, $hsYW5$reactariaprivateliveannouncerLiveAnnouncer.announce)(message, 'assertive');
        else if (wasPending.current && isFocused && !isPending) (0, $hsYW5$reactariaprivateliveannouncerLiveAnnouncer.announce)(message, 'assertive');
        wasPending.current = isPending;
    }, [
        isPending,
        isFocused,
        ariaLabelledby,
        buttonId
    ]);
    let DOMProps = (0, $hsYW5$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.onClick;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($hsYW5$react))).createElement((0, $048d76b84370f141$exports.dom).button, {
        ...(0, $hsYW5$reactariamergeProps.mergeProps)(DOMProps, renderProps, buttonProps, focusProps, hoverProps),
        // When the button is in a pending state, we want to stop implicit form submission (ie. when the user presses enter on a text input).
        // We do this by changing the button's type to button.
        type: buttonProps.type === 'submit' && isPending ? 'button' : buttonProps.type,
        id: buttonId,
        ref: ref,
        "aria-labelledby": ariaLabelledby,
        slot: props.slot || undefined,
        "aria-disabled": isPending ? 'true' : buttonProps['aria-disabled'],
        "data-disabled": props.isDisabled || undefined,
        "data-pressed": renderValues.isPressed || undefined,
        "data-hovered": isHovered || undefined,
        "data-focused": isFocused || undefined,
        "data-pending": isPending || undefined,
        "data-focus-visible": isFocusVisible || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hsYW5$react))).createElement((0, $ffc270943183f850$exports.ProgressBarContext).Provider, {
        value: {
            id: progressId
        }
    }, renderProps.children));
});
// Events to preserve when isPending is true (for tooltips and other overlays)
const $16c7f9b22cce3838$var$PRESERVED_EVENT_PATTERN = /Focus|Blur|Hover|Pointer(Enter|Leave|Over|Out)|Mouse(Enter|Leave|Over|Out)/;
function $16c7f9b22cce3838$var$useDisableInteractions(props, isPending) {
    if (isPending) {
        for(const key in props)if (key.startsWith('on') && !$16c7f9b22cce3838$var$PRESERVED_EVENT_PATTERN.test(key)) props[key] = undefined;
        props.href = undefined;
        props.target = undefined;
    }
    return props;
}


//# sourceMappingURL=Button.cjs.map
