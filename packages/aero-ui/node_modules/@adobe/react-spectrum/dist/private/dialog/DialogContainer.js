import {DialogContext as $abd082d14fc11575$export$8b93a07348a7730c} from "./context.js";
import {Modal as $76a99d57e8e1d68b$export$2b77a92f1a5ad772} from "../overlays/Modal.js";
import $gVSmD$react, {useState as $gVSmD$useState} from "react";
import {useOverlayTriggerState as $gVSmD$useOverlayTriggerState} from "react-stately/useOverlayTriggerState";

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



function $929c2962c9d61dce$export$547754aada6e339f(props) {
    let { children: children, type: type = 'modal', onDismiss: onDismiss, isDismissable: isDismissable, isKeyboardDismissDisabled: isKeyboardDismissDisabled } = props;
    let childArray = (0, $gVSmD$react).Children.toArray(children);
    if (childArray.length > 1) throw new Error('Only a single child can be passed to DialogContainer.');
    let [lastChild, setLastChild] = (0, $gVSmD$useState)(null);
    // React.Children.toArray mutates the children, and we need them to be stable
    // between renders so that the lastChild comparison works.
    let child = undefined;
    if (Array.isArray(children)) child = children.find((0, $gVSmD$react).isValidElement);
    else if (/*#__PURE__*/ (0, $gVSmD$react).isValidElement(children)) child = children;
    if (child && child !== lastChild) setLastChild(child);
    let context = {
        type: type,
        onClose: onDismiss,
        isDismissable: isDismissable
    };
    let state = (0, $gVSmD$useOverlayTriggerState)({
        isOpen: !!child,
        onOpenChange: (isOpen)=>{
            if (!isOpen) onDismiss();
        }
    });
    return /*#__PURE__*/ (0, $gVSmD$react).createElement((0, $76a99d57e8e1d68b$export$2b77a92f1a5ad772), {
        state: state,
        type: type,
        isDismissable: isDismissable,
        isKeyboardDismissDisabled: isKeyboardDismissDisabled
    }, /*#__PURE__*/ (0, $gVSmD$react).createElement((0, $abd082d14fc11575$export$8b93a07348a7730c).Provider, {
        value: context
    }, lastChild));
}


export {$929c2962c9d61dce$export$547754aada6e339f as DialogContainer};
//# sourceMappingURL=DialogContainer.js.map
