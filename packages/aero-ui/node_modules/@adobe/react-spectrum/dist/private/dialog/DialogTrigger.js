import {DialogContext as $abd082d14fc11575$export$8b93a07348a7730c} from "./context.js";
import {Modal as $76a99d57e8e1d68b$export$2b77a92f1a5ad772} from "../overlays/Modal.js";
import {Popover as $2fa1c97e743ad66b$export$5b6b19405a83ff9d} from "../overlays/Popover.js";
import {Tray as $16b239851776d94c$export$4589ed81930b555c} from "../overlays/Tray.js";
import {useIsMobileDevice as $196ab9279fe71c29$export$736bf165441b18c7} from "../utils/useIsMobileDevice.js";
import {useOverlayTriggerState as $9atwZ$useOverlayTriggerState} from "react-stately/useOverlayTriggerState";
import {PressResponder as $9atwZ$PressResponder} from "react-aria/private/interactions/PressResponder";
import $9atwZ$react, {useRef as $9atwZ$useRef, useEffect as $9atwZ$useEffect, Fragment as $9atwZ$Fragment} from "react";
import {useOverlayTrigger as $9atwZ$useOverlayTrigger} from "react-aria/useOverlayTrigger";

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








function $bcff05049955156f$var$DialogTrigger(props) {
    let { children: children, type: type = 'modal', mobileType: mobileType = type === 'popover' ? 'modal' : type, hideArrow: hideArrow, targetRef: targetRef, isDismissable: isDismissable, isKeyboardDismissDisabled: isKeyboardDismissDisabled, ...positionProps } = props;
    if (!Array.isArray(children) || children.length > 2) throw new Error('DialogTrigger must have exactly 2 children');
    // if a function is passed as the second child, it won't appear in toArray
    let [trigger, content] = children;
    // On small devices, show a modal or tray instead of a popover.
    let isMobile = (0, $196ab9279fe71c29$export$736bf165441b18c7)();
    if (isMobile) {
        // handle cases where desktop popovers need a close button for the mobile modal view
        if (type !== 'modal' && mobileType === 'modal') isDismissable = true;
        type = mobileType;
    }
    let state = (0, $9atwZ$useOverlayTriggerState)(props);
    let wasOpen = (0, $9atwZ$useRef)(false);
    (0, $9atwZ$useEffect)(()=>{
        wasOpen.current = state.isOpen;
    }, [
        state.isOpen
    ]);
    let isExiting = (0, $9atwZ$useRef)(false);
    let onExiting = ()=>isExiting.current = true;
    let onExited = ()=>isExiting.current = false;
    (0, $9atwZ$useEffect)(()=>{
        return ()=>{
            if ((wasOpen.current || isExiting.current) && type !== 'popover' && type !== 'tray' && process.env.NODE_ENV !== 'production') console.warn('A DialogTrigger unmounted while open. This is likely due to being placed within a trigger that unmounts or inside a conditional. Consider using a DialogContainer instead.');
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
    if (type === 'popover') return /*#__PURE__*/ (0, $9atwZ$react).createElement($bcff05049955156f$var$PopoverTrigger, {
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
                return /*#__PURE__*/ (0, $9atwZ$react).createElement((0, $76a99d57e8e1d68b$export$2b77a92f1a5ad772), {
                    state: state,
                    isDismissable: type === 'modal' ? isDismissable : false,
                    type: type,
                    isKeyboardDismissDisabled: isKeyboardDismissDisabled,
                    onExiting: onExiting,
                    onExited: onExited
                }, typeof content === 'function' ? content(state.close) : content);
            case 'tray':
                return /*#__PURE__*/ (0, $9atwZ$react).createElement((0, $16b239851776d94c$export$4589ed81930b555c), {
                    state: state,
                    isKeyboardDismissDisabled: isKeyboardDismissDisabled
                }, typeof content === 'function' ? content(state.close) : content);
        }
    };
    return /*#__PURE__*/ (0, $9atwZ$react).createElement($bcff05049955156f$var$DialogTriggerBase, {
        type: type,
        state: state,
        isDismissable: isDismissable,
        trigger: trigger,
        overlay: renderOverlay()
    });
}
// Support DialogTrigger inside components using CollectionBuilder.
$bcff05049955156f$var$DialogTrigger.getCollectionNode = function*(props) {
    // @ts-ignore - seems like types are wrong. Function children work fine.
    let [trigger] = (0, $9atwZ$react).Children.toArray(props.children);
    let [, content] = props.children;
    yield {
        element: trigger,
        wrapper: (element)=>/*#__PURE__*/ (0, $9atwZ$react).createElement($bcff05049955156f$var$DialogTrigger, {
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
let $bcff05049955156f$export$2e1e1122cf0cba88 = $bcff05049955156f$var$DialogTrigger;
function $bcff05049955156f$var$PopoverTrigger({ state: state, targetRef: targetRef, trigger: trigger, content: content, hideArrow: hideArrow, ...props }) {
    let triggerRef = (0, $9atwZ$useRef)(null);
    let { triggerProps: triggerProps, overlayProps: overlayProps } = (0, $9atwZ$useOverlayTrigger)({
        type: 'dialog'
    }, state, triggerRef);
    let triggerPropsWithRef = {
        ...triggerProps,
        ref: targetRef ? undefined : triggerRef
    };
    let overlay = /*#__PURE__*/ (0, $9atwZ$react).createElement((0, $2fa1c97e743ad66b$export$5b6b19405a83ff9d), {
        ...props,
        hideArrow: hideArrow,
        triggerRef: targetRef || triggerRef,
        state: state
    }, typeof content === 'function' ? content(state.close) : content);
    return /*#__PURE__*/ (0, $9atwZ$react).createElement($bcff05049955156f$var$DialogTriggerBase, {
        type: "popover",
        state: state,
        triggerProps: triggerPropsWithRef,
        dialogProps: overlayProps,
        trigger: trigger,
        overlay: overlay
    });
}
function $bcff05049955156f$var$DialogTriggerBase({ type: type, state: state, isDismissable: isDismissable, dialogProps: dialogProps = {}, triggerProps: triggerProps = {}, overlay: overlay, trigger: trigger }) {
    let context = {
        type: type,
        onClose: state.close,
        isDismissable: isDismissable,
        ...dialogProps
    };
    return /*#__PURE__*/ (0, $9atwZ$react).createElement((0, $9atwZ$Fragment), null, /*#__PURE__*/ (0, $9atwZ$react).createElement((0, $9atwZ$PressResponder), {
        ...triggerProps,
        onPress: state.toggle,
        isPressed: state.isOpen && type !== 'modal' && type !== 'fullscreen' && type !== 'fullscreenTakeover'
    }, trigger), /*#__PURE__*/ (0, $9atwZ$react).createElement((0, $abd082d14fc11575$export$8b93a07348a7730c).Provider, {
        value: context
    }, overlay));
}


export {$bcff05049955156f$export$2e1e1122cf0cba88 as DialogTrigger};
//# sourceMappingURL=DialogTrigger.js.map
