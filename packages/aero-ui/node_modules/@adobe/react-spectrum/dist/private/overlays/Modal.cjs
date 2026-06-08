var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../modal_vars.css");
var $999d8e793f53295d$exports = require("../modal_vars_css.cjs");
var $906ecc59dea2a2ae$exports = require("./Overlay.cjs");
require("./overlays.css");
var $febdbd88af87631e$exports = require("./overlays_css.cjs");
var $1607bc090e03ac72$exports = require("./Underlay.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $lzfih$reactariauseModalOverlay = require("react-aria/useModalOverlay");
var $lzfih$react = require("react");
var $lzfih$reactariauseObjectRef = require("react-aria/useObjectRef");
var $lzfih$reactariaprivateutilsuseViewportSize = require("react-aria/private/utils/useViewportSize");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Modal", function () { return $cc6c54efa1ae43bd$export$2b77a92f1a5ad772; });
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










const $cc6c54efa1ae43bd$export$2b77a92f1a5ad772 = /*#__PURE__*/ (0, $lzfih$react.forwardRef)(function Modal(props, ref) {
    let { children: children, state: state, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let wrapperRef = (0, $lzfih$react.useRef)(null);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($lzfih$react))).createElement((0, $906ecc59dea2a2ae$exports.Overlay), {
        ...otherProps,
        isOpen: state.isOpen,
        nodeRef: wrapperRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($lzfih$react))).createElement($cc6c54efa1ae43bd$var$ModalWrapper, {
        ...props,
        wrapperRef: wrapperRef,
        ref: domRef
    }, children));
});
let $cc6c54efa1ae43bd$var$typeMap = {
    fullscreen: 'fullscreen',
    fullscreenTakeover: 'fullscreenTakeover'
};
let $cc6c54efa1ae43bd$var$ModalWrapper = /*#__PURE__*/ (0, $lzfih$react.forwardRef)(function(props, ref) {
    let { type: type, children: children, state: state, isOpen: isOpen, wrapperRef: wrapperRef } = props;
    let typeVariant = type != null ? $cc6c54efa1ae43bd$var$typeMap[type] : undefined;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let objRef = (0, $lzfih$reactariauseObjectRef.useObjectRef)(ref);
    let { modalProps: modalProps, underlayProps: underlayProps } = (0, $lzfih$reactariauseModalOverlay.useModalOverlay)(props, state, objRef);
    let wrapperClassName = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($999d8e793f53295d$exports))), 'spectrum-Modal-wrapper', (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($febdbd88af87631e$exports))), 'spectrum-Modal-wrapper', 'react-spectrum-Modal-wrapper'));
    let modalClassName = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($999d8e793f53295d$exports))), 'spectrum-Modal', {
        'is-open': isOpen
    }, (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($febdbd88af87631e$exports))), 'spectrum-Modal', 'react-spectrum-Modal'), {
        [`spectrum-Modal--${typeVariant}`]: typeVariant
    }, styleProps.className);
    let viewport = (0, $lzfih$reactariaprivateutilsuseViewportSize.useViewportSize)();
    let style = {
        '--spectrum-visual-viewport-height': viewport.height + 'px'
    };
    // Attach Transition's nodeRef to outer most wrapper for node.reflow: https://github.com/reactjs/react-transition-group/blob/c89f807067b32eea6f68fd6c622190d88ced82e2/src/Transition.js#L231
    return /*#__PURE__*/ (0, ($parcel$interopDefault($lzfih$react))).createElement("div", {
        ref: wrapperRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($lzfih$react))).createElement((0, $1607bc090e03ac72$exports.Underlay), {
        ...underlayProps,
        isOpen: isOpen
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($lzfih$react))).createElement("div", {
        className: wrapperClassName,
        style: style
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($lzfih$react))).createElement("div", {
        ...styleProps,
        ...modalProps,
        ref: objRef,
        className: modalClassName,
        "data-testid": "modal"
    }, children)));
});


//# sourceMappingURL=Modal.cjs.map
