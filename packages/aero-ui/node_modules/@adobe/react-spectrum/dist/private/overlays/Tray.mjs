import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Overlay as $90fcff6c53c7cf60$export$c6fdb837b070b4ff} from "./Overlay.mjs";
import "./overlays.css";
import $eFhVp$overlays_cssmjs from "./overlays_css.mjs";
import "../tray_vars.css";
import $eFhVp$tray_vars_cssmjs from "../tray_vars_css.mjs";
import {Underlay as $9d27f060fca68ae8$export$f360afc887607b02} from "./Underlay.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useModalOverlay as $eFhVp$useModalOverlay} from "react-aria/useModalOverlay";
import {DismissButton as $eFhVp$DismissButton} from "react-aria/Overlay";
import $eFhVp$react, {forwardRef as $eFhVp$forwardRef, useRef as $eFhVp$useRef} from "react";
import {useObjectRef as $eFhVp$useObjectRef} from "react-aria/useObjectRef";
import {useViewportSize as $eFhVp$useViewportSize} from "react-aria/private/utils/useViewportSize";


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











const $9fca089dca5508dc$export$4589ed81930b555c = /*#__PURE__*/ (0, $eFhVp$forwardRef)(function Tray(props, ref) {
    let { children: children, state: state, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let wrapperRef = (0, $eFhVp$useRef)(null);
    return /*#__PURE__*/ (0, $eFhVp$react).createElement((0, $90fcff6c53c7cf60$export$c6fdb837b070b4ff), {
        ...otherProps,
        isOpen: state.isOpen,
        nodeRef: wrapperRef
    }, /*#__PURE__*/ (0, $eFhVp$react).createElement($9fca089dca5508dc$var$TrayWrapper, {
        ...props,
        wrapperRef: wrapperRef,
        ref: domRef
    }, children));
});
let $9fca089dca5508dc$var$TrayWrapper = /*#__PURE__*/ (0, $eFhVp$forwardRef)(function(props, ref) {
    let { children: children, isOpen: isOpen, isFixedHeight: isFixedHeight, state: state, wrapperRef: wrapperRef } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let objRef = (0, $eFhVp$useObjectRef)(ref);
    let { modalProps: modalProps, underlayProps: underlayProps } = (0, $eFhVp$useModalOverlay)({
        ...props,
        isDismissable: true
    }, state, objRef);
    // We need to measure the window's height in JS rather than using percentages in CSS
    // so that contents (e.g. menu) can inherit the max-height properly. Using percentages
    // does not work properly because there is nothing to base the percentage on.
    // We cannot use vh units because mobile browsers adjust the window height dynamically
    // when the address bar/bottom toolbars show and hide on scroll and vh units are fixed.
    // Also, the visual viewport is smaller than the layout viewport when the virtual keyboard
    // is up, so use the VisualViewport API to ensure the tray is displayed above the keyboard.
    let viewport = (0, $eFhVp$useViewportSize)();
    let wrapperStyle = {
        '--spectrum-visual-viewport-height': viewport.height + 'px',
        // position: fixed elements are clipped by Safari on iOS 26, so we use
        // position: absolute and manually set the top to the scrollY.
        // The page can't scroll while the tray is open so this doesn't need to update.
        top: typeof window !== 'undefined' ? window.scrollY : 0
    };
    let wrapperClassName = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eFhVp$tray_vars_cssmjs))), 'spectrum-Tray-wrapper');
    let className = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eFhVp$tray_vars_cssmjs))), 'spectrum-Tray', {
        'is-open': isOpen,
        'spectrum-Tray--fixedHeight': isFixedHeight
    }, (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eFhVp$overlays_cssmjs))), 'spectrum-Tray', 'react-spectrum-Tray'), styleProps.className);
    // Attach Transition's nodeRef to outer most wrapper for node.reflow: https://github.com/reactjs/react-transition-group/blob/c89f807067b32eea6f68fd6c622190d88ced82e2/src/Transition.js#L231
    return /*#__PURE__*/ (0, $eFhVp$react).createElement("div", {
        ref: wrapperRef
    }, /*#__PURE__*/ (0, $eFhVp$react).createElement((0, $9d27f060fca68ae8$export$f360afc887607b02), {
        ...underlayProps,
        isOpen: isOpen
    }), /*#__PURE__*/ (0, $eFhVp$react).createElement("div", {
        className: wrapperClassName,
        style: wrapperStyle
    }, /*#__PURE__*/ (0, $eFhVp$react).createElement("div", {
        ...styleProps,
        ...modalProps,
        className: className,
        ref: objRef,
        "data-testid": "tray"
    }, /*#__PURE__*/ (0, $eFhVp$react).createElement((0, $eFhVp$DismissButton), {
        onDismiss: state.close
    }), children, /*#__PURE__*/ (0, $eFhVp$react).createElement((0, $eFhVp$DismissButton), {
        onDismiss: state.close
    }))));
});


export {$9fca089dca5508dc$export$4589ed81930b555c as Tray};
//# sourceMappingURL=Tray.mjs.map
