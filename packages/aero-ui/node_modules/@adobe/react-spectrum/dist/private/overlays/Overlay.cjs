var $1048bdce1c849903$exports = require("./OpenTransition.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $gIQqO$react = require("react");
var $gIQqO$reactariaOverlay = require("react-aria/Overlay");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Overlay", function () { return $906ecc59dea2a2ae$export$c6fdb837b070b4ff; });
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



const $906ecc59dea2a2ae$export$c6fdb837b070b4ff = /*#__PURE__*/ (0, ($parcel$interopDefault($gIQqO$react))).forwardRef(function Overlay(props, ref) {
    let { children: children, isOpen: isOpen, disableFocusManagement: disableFocusManagement, shouldContainFocus: shouldContainFocus, container: container, onEnter: onEnter, onEntering: onEntering, onEntered: onEntered, onExit: onExit, onExiting: onExiting, onExited: onExited, nodeRef: nodeRef } = props;
    let [exited, setExited] = (0, $gIQqO$react.useState)(!isOpen);
    let handleEntered = (0, $gIQqO$react.useCallback)(()=>{
        setExited(false);
        if (onEntered) onEntered();
    }, [
        onEntered
    ]);
    let handleExited = (0, $gIQqO$react.useCallback)(()=>{
        setExited(true);
        if (onExited) onExited();
    }, [
        onExited
    ]);
    // Don't un-render the overlay while it's transitioning out.
    let mountOverlay = isOpen || !exited;
    if (!mountOverlay) // Don't bother showing anything if we don't have to.
    return null;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($gIQqO$react))).createElement((0, $gIQqO$reactariaOverlay.Overlay), {
        portalContainer: container,
        disableFocusManagement: disableFocusManagement,
        shouldContainFocus: shouldContainFocus,
        isExiting: !isOpen
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gIQqO$react))).createElement((0, $544fc82701fc93e9$exports.Provider), {
        ref: ref,
        UNSAFE_style: {
            background: 'transparent',
            isolation: 'isolate'
        },
        isDisabled: false
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gIQqO$react))).createElement((0, $1048bdce1c849903$exports.OpenTransition), {
        in: isOpen,
        appear: true,
        onExit: onExit,
        onExiting: onExiting,
        onExited: handleExited,
        onEnter: onEnter,
        onEntering: onEntering,
        onEntered: handleEntered,
        nodeRef: nodeRef
    }, children)));
});


//# sourceMappingURL=Overlay.cjs.map
