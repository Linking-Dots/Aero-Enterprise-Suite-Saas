import {DialogContext as $45cab99fd43a8f38$export$8b93a07348a7730c} from "./context.mjs";
import {Modal as $10c5cb47049d7262$export$2b77a92f1a5ad772} from "../overlays/Modal.mjs";
import {Popover as $3a473e3b7032f626$export$5b6b19405a83ff9d} from "../overlays/Popover.mjs";
import {Tray as $9fca089dca5508dc$export$4589ed81930b555c} from "../overlays/Tray.mjs";
import {useIsMobileDevice as $f357d4aae54bf1ff$export$736bf165441b18c7} from "../utils/useIsMobileDevice.mjs";
import {useOverlayTriggerState as $ai3dT$useOverlayTriggerState} from "react-stately/useOverlayTriggerState";
import {PressResponder as $ai3dT$PressResponder} from "react-aria/private/interactions/PressResponder";
import $ai3dT$react, {useRef as $ai3dT$useRef, useEffect as $ai3dT$useEffect, Fragment as $ai3dT$Fragment} from "react";
import {useOverlayTrigger as $ai3dT$useOverlayTrigger} from "react-aria/useOverlayTrigger";

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








function $41e0ad2a982a34c5$var$DialogTrigger(props) {
    let { children: children, type: type = 'modal', mobileType: mobileType = type === 'popover' ? 'modal' : type, hideArrow: hideArrow, targetRef: targetRef, isDismissable: isDismissable, isKeyboardDismissDisabled: isKeyboardDismissDisabled, ...positionProps } = props;
    if (!Array.isArray(children) || children.length > 2) throw new Error('DialogTrigger must have exactly 2 children');
    // if a function is passed as the second child, it won't appear in toArray
    let [trigger, content] = children;
    // On small devices, show a modal or tray instead of a popover.
    let isMobile = (0, $f357d4aae54bf1ff$export$736bf165441b18c7)();
    if (isMobile) {
        // handle cases where desktop popovers need a close button for the mobile modal view
        if (type !== 'modal' && mobileType === 'modal') isDismissable = true;
        type = mobileType;
    }
    let state = (0, $ai3dT$useOverlayTriggerState)(props);
    let wasOpen = (0, $ai3dT$useRef)(false);
    (0, $ai3dT$useEffect)(()=>{
        wasOpen.current = state.isOpen;
    }, [
        state.isOpen
    ]);
    let isExiting = (0, $ai3dT$useRef)(false);
    let onExiting = ()=>isExiting.current = true;
    let onExited = ()=>isExiting.current = false;
    (0, $ai3dT$useEffect)(()=>{
        return ()=>{
            if ((wasOpen.current || isExiting.current) && type !== 'popover' && type !== 'tray' && process.env.NODE_ENV !== 'production') console.warn('A DialogTrigger unmounted while open. This is likely due to being placed within a trigger that unmounts or inside a conditional. Consider using a DialogContainer instead.');
        };
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);
    if (type === 'popover') return /*#__PURE__*/ (0, $ai3dT$react).createElement($41e0ad2a982a34c5$var$PopoverTrigger, {
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
                return /*#__PURE__*/ (0, $ai3dT$react).createElement((0, $10c5cb47049d7262$export$2b77a92f1a5ad772), {
                    state: state,
                    isDismissable: type === 'modal' ? isDismissable : false,
                    type: type,
                    isKeyboardDismissDisabled: isKeyboardDismissDisabled,
                    onExiting: onExiting,
                    onExited: onExited
                }, typeof content === 'function' ? content(state.close) : content);
            case 'tray':
                return /*#__PURE__*/ (0, $ai3dT$react).createElement((0, $9fca089dca5508dc$export$4589ed81930b555c), {
                    state: state,
                    isKeyboardDismissDisabled: isKeyboardDismissDisabled
                }, typeof content === 'function' ? content(state.close) : content);
        }
    };
    return /*#__PURE__*/ (0, $ai3dT$react).createElement($41e0ad2a982a34c5$var$DialogTriggerBase, {
        type: type,
        state: state,
        isDismissable: isDismissable,
        trigger: trigger,
        overlay: renderOverlay()
    });
}
// Support DialogTrigger inside components using CollectionBuilder.
$41e0ad2a982a34c5$var$DialogTrigger.getCollectionNode = function*(props) {
    // @ts-ignore - seems like types are wrong. Function children work fine.
    let [trigger] = (0, $ai3dT$react).Children.toArray(props.children);
    let [, content] = props.children;
    yield {
        element: trigger,
        wrapper: (element)=>/*#__PURE__*/ (0, $ai3dT$react).createElement($41e0ad2a982a34c5$var$DialogTrigger, {
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
let $41e0ad2a982a34c5$export$2e1e1122cf0cba88 = $41e0ad2a982a34c5$var$DialogTrigger;
function $41e0ad2a982a34c5$var$PopoverTrigger({ state: state, targetRef: targetRef, trigger: trigger, content: content, hideArrow: hideArrow, ...props }) {
    let triggerRef = (0, $ai3dT$useRef)(null);
    let { triggerProps: triggerProps, overlayProps: overlayProps } = (0, $ai3dT$useOverlayTrigger)({
        type: 'dialog'
    }, state, triggerRef);
    let triggerPropsWithRef = {
        ...triggerProps,
        ref: targetRef ? undefined : triggerRef
    };
    let overlay = /*#__PURE__*/ (0, $ai3dT$react).createElement((0, $3a473e3b7032f626$export$5b6b19405a83ff9d), {
        ...props,
        hideArrow: hideArrow,
        triggerRef: targetRef || triggerRef,
        state: state
    }, typeof content === 'function' ? content(state.close) : content);
    return /*#__PURE__*/ (0, $ai3dT$react).createElement($41e0ad2a982a34c5$var$DialogTriggerBase, {
        type: "popover",
        state: state,
        triggerProps: triggerPropsWithRef,
        dialogProps: overlayProps,
        trigger: trigger,
        overlay: overlay
    });
}
function $41e0ad2a982a34c5$var$DialogTriggerBase({ type: type, state: state, isDismissable: isDismissable, dialogProps: dialogProps = {}, triggerProps: triggerProps = {}, overlay: overlay, trigger: trigger }) {
    let context = {
        type: type,
        onClose: state.close,
        isDismissable: isDismissable,
        ...dialogProps
    };
    return /*#__PURE__*/ (0, $ai3dT$react).createElement((0, $ai3dT$Fragment), null, /*#__PURE__*/ (0, $ai3dT$react).createElement((0, $ai3dT$PressResponder), {
        ...triggerProps,
        onPress: state.toggle,
        isPressed: state.isOpen && type !== 'modal' && type !== 'fullscreen' && type !== 'fullscreenTakeover'
    }, trigger), /*#__PURE__*/ (0, $ai3dT$react).createElement((0, $45cab99fd43a8f38$export$8b93a07348a7730c).Provider, {
        value: context
    }, overlay));
}


export {$41e0ad2a982a34c5$export$2e1e1122cf0cba88 as DialogTrigger};
//# sourceMappingURL=DialogTrigger.mjs.map
