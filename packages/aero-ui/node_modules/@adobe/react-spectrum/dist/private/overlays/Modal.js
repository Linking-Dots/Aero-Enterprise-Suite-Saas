import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import "../modal_vars.css";
import $9rPad$modal_vars_cssmjs from "../modal_vars_css.mjs";
import {Overlay as $d73ca11fb7e7e69a$export$c6fdb837b070b4ff} from "./Overlay.js";
import "./overlays.css";
import $9rPad$overlays_cssmjs from "./overlays_css.mjs";
import {Underlay as $29c7092a192e9a93$export$f360afc887607b02} from "./Underlay.js";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useModalOverlay as $9rPad$useModalOverlay} from "react-aria/useModalOverlay";
import $9rPad$react, {forwardRef as $9rPad$forwardRef, useRef as $9rPad$useRef} from "react";
import {useObjectRef as $9rPad$useObjectRef} from "react-aria/useObjectRef";
import {useViewportSize as $9rPad$useViewportSize} from "react-aria/private/utils/useViewportSize";


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










const $76a99d57e8e1d68b$export$2b77a92f1a5ad772 = /*#__PURE__*/ (0, $9rPad$forwardRef)(function Modal(props, ref) {
    let { children: children, state: state, ...otherProps } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let wrapperRef = (0, $9rPad$useRef)(null);
    return /*#__PURE__*/ (0, $9rPad$react).createElement((0, $d73ca11fb7e7e69a$export$c6fdb837b070b4ff), {
        ...otherProps,
        isOpen: state.isOpen,
        nodeRef: wrapperRef
    }, /*#__PURE__*/ (0, $9rPad$react).createElement($76a99d57e8e1d68b$var$ModalWrapper, {
        ...props,
        wrapperRef: wrapperRef,
        ref: domRef
    }, children));
});
let $76a99d57e8e1d68b$var$typeMap = {
    fullscreen: 'fullscreen',
    fullscreenTakeover: 'fullscreenTakeover'
};
let $76a99d57e8e1d68b$var$ModalWrapper = /*#__PURE__*/ (0, $9rPad$forwardRef)(function(props, ref) {
    let { type: type, children: children, state: state, isOpen: isOpen, wrapperRef: wrapperRef } = props;
    let typeVariant = type != null ? $76a99d57e8e1d68b$var$typeMap[type] : undefined;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let objRef = (0, $9rPad$useObjectRef)(ref);
    let { modalProps: modalProps, underlayProps: underlayProps } = (0, $9rPad$useModalOverlay)(props, state, objRef);
    let wrapperClassName = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9rPad$modal_vars_cssmjs))), 'spectrum-Modal-wrapper', (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9rPad$overlays_cssmjs))), 'spectrum-Modal-wrapper', 'react-spectrum-Modal-wrapper'));
    let modalClassName = (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9rPad$modal_vars_cssmjs))), 'spectrum-Modal', {
        'is-open': isOpen
    }, (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9rPad$overlays_cssmjs))), 'spectrum-Modal', 'react-spectrum-Modal'), {
        [`spectrum-Modal--${typeVariant}`]: typeVariant
    }, styleProps.className);
    let viewport = (0, $9rPad$useViewportSize)();
    let style = {
        '--spectrum-visual-viewport-height': viewport.height + 'px'
    };
    // Attach Transition's nodeRef to outer most wrapper for node.reflow: https://github.com/reactjs/react-transition-group/blob/c89f807067b32eea6f68fd6c622190d88ced82e2/src/Transition.js#L231
    return /*#__PURE__*/ (0, $9rPad$react).createElement("div", {
        ref: wrapperRef
    }, /*#__PURE__*/ (0, $9rPad$react).createElement((0, $29c7092a192e9a93$export$f360afc887607b02), {
        ...underlayProps,
        isOpen: isOpen
    }), /*#__PURE__*/ (0, $9rPad$react).createElement("div", {
        className: wrapperClassName,
        style: style
    }, /*#__PURE__*/ (0, $9rPad$react).createElement("div", {
        ...styleProps,
        ...modalProps,
        ref: objRef,
        className: modalClassName,
        "data-testid": "modal"
    }, children)));
});


export {$76a99d57e8e1d68b$export$2b77a92f1a5ad772 as Modal};
//# sourceMappingURL=Modal.js.map
