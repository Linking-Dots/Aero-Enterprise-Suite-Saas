import {OpenTransition as $dd2af037c5de1a3e$export$b847a40ee92eff38} from "./OpenTransition.js";
import {Provider as $089943c7a219141c$export$2881499e37b75b9a} from "../provider/Provider.js";
import $78SFs$react, {useState as $78SFs$useState, useCallback as $78SFs$useCallback} from "react";
import {Overlay as $78SFs$Overlay} from "react-aria/Overlay";

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



const $d73ca11fb7e7e69a$export$c6fdb837b070b4ff = /*#__PURE__*/ (0, $78SFs$react).forwardRef(function Overlay(props, ref) {
    let { children: children, isOpen: isOpen, disableFocusManagement: disableFocusManagement, shouldContainFocus: shouldContainFocus, container: container, onEnter: onEnter, onEntering: onEntering, onEntered: onEntered, onExit: onExit, onExiting: onExiting, onExited: onExited, nodeRef: nodeRef } = props;
    let [exited, setExited] = (0, $78SFs$useState)(!isOpen);
    let handleEntered = (0, $78SFs$useCallback)(()=>{
        setExited(false);
        if (onEntered) onEntered();
    }, [
        onEntered
    ]);
    let handleExited = (0, $78SFs$useCallback)(()=>{
        setExited(true);
        if (onExited) onExited();
    }, [
        onExited
    ]);
    // Don't un-render the overlay while it's transitioning out.
    let mountOverlay = isOpen || !exited;
    if (!mountOverlay) // Don't bother showing anything if we don't have to.
    return null;
    return /*#__PURE__*/ (0, $78SFs$react).createElement((0, $78SFs$Overlay), {
        portalContainer: container,
        disableFocusManagement: disableFocusManagement,
        shouldContainFocus: shouldContainFocus,
        isExiting: !isOpen
    }, /*#__PURE__*/ (0, $78SFs$react).createElement((0, $089943c7a219141c$export$2881499e37b75b9a), {
        ref: ref,
        UNSAFE_style: {
            background: 'transparent',
            isolation: 'isolate'
        },
        isDisabled: false
    }, /*#__PURE__*/ (0, $78SFs$react).createElement((0, $dd2af037c5de1a3e$export$b847a40ee92eff38), {
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


export {$d73ca11fb7e7e69a$export$c6fdb837b070b4ff as Overlay};
//# sourceMappingURL=Overlay.js.map
