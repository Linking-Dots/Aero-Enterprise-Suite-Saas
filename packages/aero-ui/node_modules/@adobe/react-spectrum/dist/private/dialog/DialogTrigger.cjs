var $4965a9907649f3b8$exports = require("./context.cjs");
var $cc6c54efa1ae43bd$exports = require("../overlays/Modal.cjs");
var $39ed1c805b59752f$exports = require("../overlays/Popover.cjs");
var $378dee1409fe2937$exports = require("../overlays/Tray.cjs");
var $0b97cdf6ccc1e502$exports = require("../utils/useIsMobileDevice.cjs");
var $1VEXN$reactstatelyuseOverlayTriggerState = require("react-stately/useOverlayTriggerState");
var $1VEXN$reactariaprivateinteractionsPressResponder = require("react-aria/private/interactions/PressResponder");
var $1VEXN$react = require("react");
var $1VEXN$reactariauseOverlayTrigger = require("react-aria/useOverlayTrigger");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DialogTrigger", function () { return $d4a85248c617d550$export$2e1e1122cf0cba88; });
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








function $d4a85248c617d550$var$DialogTrigger(props) {
    let { children: children, type: type = 'modal', mobileType: mobileType = type === 'popover' ? 'modal' : type, hideArrow: hideArrow, targetRef: targetRef, isDismissable: isDismissable, isKeyboardDismissDisabled: isKeyboardDismissDisabled, ...positionProps } = props;
    if (!Array.isArray(children) || children.length > 2) throw new Error('DialogTrigger must have exactly 2 children');
    // if a function is passed as the second child, it won't appear in toArray
    let [trigger, content] = children;
    // On small devices, show a modal or tray instead of a popover.
    let isMobile = (0, $0b97cdf6ccc1e502$exports.useIsMobileDevice)();
    if (isMobile) {
        // handle cases where desktop popovers need a close button for the mobile modal view
        if (type !== 'modal' && mobileType === 'modal') isDismissable = true;
        type = mobileType;
    }
    let state = (0, $1VEXN$reactstatelyuseOverlayTriggerState.useOverlayTriggerState)(props);
    let wasOpen = (0, $1VEXN$react.useRef)(false);
    (0, $1VEXN$react.useEffect)(()=>{
        wasOpen.current = state.isOpen;
    }, [
        state.isOpen
    ]);
    let isExiting = (0, $1VEXN$react.useRef)(false);
    let onExiting = ()=>isExiting.current = true;
    let onExited = ()=>isExiting.current = false;
    (0, $1VEXN$react.useEffect)(()=>{
        return ()=>{
            if ((wasOpen.current || isExiting.current) && type !== 'popover' && type !== 'tray' && process.env.NODE_ENV !== 'production') console.warn('A DialogTrigger unmounted while open. This is likely due to being placed within a trigger that unmounts or inside a conditional. Consider using a DialogContainer instead.');
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
    if (type === 'popover') return /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement($d4a85248c617d550$var$PopoverTrigger, {
        ...positionProps,
        state: state,
        targetRef: targetRef,
        trigger: trigger,
        content: content,
        isKeyboardDismissDisabled: isKeyboardDismissDisabled,
        hideArrow: hideArrow
    });
    let renderOverlay = ()=>{
        switch(type){
            case 'fullscreen':
            case 'fullscreenTakeover':
            case 'modal':
                return /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement((0, $cc6c54efa1ae43bd$exports.Modal), {
                    state: state,
                    isDismissable: type === 'modal' ? isDismissable : false,
                    type: type,
                    isKeyboardDismissDisabled: isKeyboardDismissDisabled,
                    onExiting: onExiting,
                    onExited: onExited
                }, typeof content === 'function' ? content(state.close) : content);
            case 'tray':
                return /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement((0, $378dee1409fe2937$exports.Tray), {
                    state: state,
                    isKeyboardDismissDisabled: isKeyboardDismissDisabled
                }, typeof content === 'function' ? content(state.close) : content);
        }
    };
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement($d4a85248c617d550$var$DialogTriggerBase, {
        type: type,
        state: state,
        isDismissable: isDismissable,
        trigger: trigger,
        overlay: renderOverlay()
    });
}
// Support DialogTrigger inside components using CollectionBuilder.
$d4a85248c617d550$var$DialogTrigger.getCollectionNode = function*(props) {
    // @ts-ignore - seems like types are wrong. Function children work fine.
    let [trigger] = (0, ($parcel$interopDefault($1VEXN$react))).Children.toArray(props.children);
    let [, content] = props.children;
    yield {
        element: trigger,
        wrapper: (element)=>/*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement($d4a85248c617d550$var$DialogTrigger, {
                key: element.key,
                ...props
            }, element, content)
    };
};
/**
 * DialogTrigger serves as a wrapper around a Dialog and its associated trigger, linking the
 * Dialog's open state with the trigger's press state. Additionally, it allows you to customize the
 * type and positioning of the Dialog.
 */ // We don't want getCollectionNode to show up in the type definition
let $d4a85248c617d550$export$2e1e1122cf0cba88 = $d4a85248c617d550$var$DialogTrigger;
function $d4a85248c617d550$var$PopoverTrigger({ state: state, targetRef: targetRef, trigger: trigger, content: content, hideArrow: hideArrow, ...props }) {
    let triggerRef = (0, $1VEXN$react.useRef)(null);
    let { triggerProps: triggerProps, overlayProps: overlayProps } = (0, $1VEXN$reactariauseOverlayTrigger.useOverlayTrigger)({
        type: 'dialog'
    }, state, triggerRef);
    let triggerPropsWithRef = {
        ...triggerProps,
        ref: targetRef ? undefined : triggerRef
    };
    let overlay = /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement((0, $39ed1c805b59752f$exports.Popover), {
        ...props,
        hideArrow: hideArrow,
        triggerRef: targetRef || triggerRef,
        state: state
    }, typeof content === 'function' ? content(state.close) : content);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement($d4a85248c617d550$var$DialogTriggerBase, {
        type: "popover",
        state: state,
        triggerProps: triggerPropsWithRef,
        dialogProps: overlayProps,
        trigger: trigger,
        overlay: overlay
    });
}
function $d4a85248c617d550$var$DialogTriggerBase({ type: type, state: state, isDismissable: isDismissable, dialogProps: dialogProps = {}, triggerProps: triggerProps = {}, overlay: overlay, trigger: trigger }) {
    let context = {
        type: type,
        onClose: state.close,
        isDismissable: isDismissable,
        ...dialogProps
    };
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement((0, $1VEXN$react.Fragment), null, /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement((0, $1VEXN$reactariaprivateinteractionsPressResponder.PressResponder), {
        ...triggerProps,
        onPress: state.toggle,
        isPressed: state.isOpen && type !== 'modal' && type !== 'fullscreen' && type !== 'fullscreenTakeover'
    }, trigger), /*#__PURE__*/ (0, ($parcel$interopDefault($1VEXN$react))).createElement((0, $4965a9907649f3b8$exports.DialogContext).Provider, {
        value: context
    }, overlay));
}


//# sourceMappingURL=DialogTrigger.cjs.map
