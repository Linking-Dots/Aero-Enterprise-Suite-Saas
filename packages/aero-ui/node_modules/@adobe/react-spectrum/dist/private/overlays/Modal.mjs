import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import "../modal_vars.css";
import $81DEF$modal_vars_cssmjs from "../modal_vars_css.mjs";
import {Overlay as $90fcff6c53c7cf60$export$c6fdb837b070b4ff} from "./Overlay.mjs";
import "./overlays.css";
import $81DEF$overlays_cssmjs from "./overlays_css.mjs";
import {Underlay as $9d27f060fca68ae8$export$f360afc887607b02} from "./Underlay.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useModalOverlay as $81DEF$useModalOverlay} from "react-aria/useModalOverlay";
import $81DEF$react, {forwardRef as $81DEF$forwardRef, useRef as $81DEF$useRef} from "react";
import {useObjectRef as $81DEF$useObjectRef} from "react-aria/useObjectRef";
import {useViewportSize as $81DEF$useViewportSize} from "react-aria/private/utils/useViewportSize";


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










const $10c5cb47049d7262$export$2b77a92f1a5ad772 = /*#__PURE__*/ (0, $81DEF$forwardRef)(function Modal(props, ref) {
    let { children: children, state: state, ...otherProps } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let wrapperRef = (0, $81DEF$useRef)(null);
    return /*#__PURE__*/ (0, $81DEF$react).createElement((0, $90fcff6c53c7cf60$export$c6fdb837b070b4ff), {
        ...otherProps,
        isOpen: state.isOpen,
        nodeRef: wrapperRef
    }, /*#__PURE__*/ (0, $81DEF$react).createElement($10c5cb47049d7262$var$ModalWrapper, {
        ...props,
        wrapperRef: wrapperRef,
        ref: domRef
    }, children));
});
let $10c5cb47049d7262$var$typeMap = {
    fullscreen: 'fullscreen',
    fullscreenTakeover: 'fullscreenTakeover'
};
let $10c5cb47049d7262$var$ModalWrapper = /*#__PURE__*/ (0, $81DEF$forwardRef)(function(props, ref) {
    let { type: type, children: children, state: state, isOpen: isOpen, wrapperRef: wrapperRef } = props;
    let typeVariant = type != null ? $10c5cb47049d7262$var$typeMap[type] : undefined;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let objRef = (0, $81DEF$useObjectRef)(ref);
    let { modalProps: modalProps, underlayProps: underlayProps } = (0, $81DEF$useModalOverlay)(props, state, objRef);
    let wrapperClassName = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($81DEF$modal_vars_cssmjs))), 'spectrum-Modal-wrapper', (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($81DEF$overlays_cssmjs))), 'spectrum-Modal-wrapper', 'react-spectrum-Modal-wrapper'));
    let modalClassName = (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($81DEF$modal_vars_cssmjs))), 'spectrum-Modal', {
        'is-open': isOpen
    }, (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($81DEF$overlays_cssmjs))), 'spectrum-Modal', 'react-spectrum-Modal'), {
        [`spectrum-Modal--${typeVariant}`]: typeVariant
    }, styleProps.className);
    let viewport = (0, $81DEF$useViewportSize)();
    let style = {
        '--spectrum-visual-viewport-height': viewport.height + 'px'
    };
    // Attach Transition's nodeRef to outer most wrapper for node.reflow: https://github.com/reactjs/react-transition-group/blob/c89f807067b32eea6f68fd6c622190d88ced82e2/src/Transition.js#L231
    return /*#__PURE__*/ (0, $81DEF$react).createElement("div", {
        ref: wrapperRef
    }, /*#__PURE__*/ (0, $81DEF$react).createElement((0, $9d27f060fca68ae8$export$f360afc887607b02), {
        ...underlayProps,
        isOpen: isOpen
    }), /*#__PURE__*/ (0, $81DEF$react).createElement("div", {
        className: wrapperClassName,
        style: style
    }, /*#__PURE__*/ (0, $81DEF$react).createElement("div", {
        ...styleProps,
        ...modalProps,
        ref: objRef,
        className: modalClassName,
        "data-testid": "modal"
    }, children)));
});


export {$10c5cb47049d7262$export$2b77a92f1a5ad772 as Modal};
//# sourceMappingURL=Modal.mjs.map
