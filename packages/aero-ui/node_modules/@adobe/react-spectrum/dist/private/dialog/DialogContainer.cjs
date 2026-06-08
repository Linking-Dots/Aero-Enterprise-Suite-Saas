var $4965a9907649f3b8$exports = require("./context.cjs");
var $cc6c54efa1ae43bd$exports = require("../overlays/Modal.cjs");
var $jqEAx$react = require("react");
var $jqEAx$reactstatelyuseOverlayTriggerState = require("react-stately/useOverlayTriggerState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "DialogContainer", function () { return $70d6b3ed151ccd1b$export$547754aada6e339f; });
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



function $70d6b3ed151ccd1b$export$547754aada6e339f(props) {
    let { children: children, type: type = 'modal', onDismiss: onDismiss, isDismissable: isDismissable, isKeyboardDismissDisabled: isKeyboardDismissDisabled } = props;
    let childArray = (0, ($parcel$interopDefault($jqEAx$react))).Children.toArray(children);
    if (childArray.length > 1) throw new Error('Only a single child can be passed to DialogContainer.');
    let [lastChild, setLastChild] = (0, $jqEAx$react.useState)(null);
    // React.Children.toArray mutates the children, and we need them to be stable
    // between renders so that the lastChild comparison works.
    let child = undefined;
    if (Array.isArray(children)) child = children.find((0, ($parcel$interopDefault($jqEAx$react))).isValidElement);
    else if (/*#__PURE__*/ (0, ($parcel$interopDefault($jqEAx$react))).isValidElement(children)) child = children;
    if (child && child !== lastChild) setLastChild(child);
    let context = {
        type: type,
        onClose: onDismiss,
        isDismissable: isDismissable
    };
    let state = (0, $jqEAx$reactstatelyuseOverlayTriggerState.useOverlayTriggerState)({
        isOpen: !!child,
        onOpenChange: (isOpen)=>{
            if (!isOpen) onDismiss();
        }
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($jqEAx$react))).createElement((0, $cc6c54efa1ae43bd$exports.Modal), {
        state: state,
        type: type,
        isDismissable: isDismissable,
        isKeyboardDismissDisabled: isKeyboardDismissDisabled
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($jqEAx$react))).createElement((0, $4965a9907649f3b8$exports.DialogContext).Provider, {
        value: context
    }, lastChild));
}


//# sourceMappingURL=DialogContainer.cjs.map
