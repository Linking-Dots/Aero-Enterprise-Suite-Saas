var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $906ecc59dea2a2ae$exports = require("./Overlay.cjs");
require("./overlays.css");
var $febdbd88af87631e$exports = require("./overlays_css.cjs");
require("../tray_vars.css");
var $b8a5afb76663131a$exports = require("../tray_vars_css.cjs");
var $1607bc090e03ac72$exports = require("./Underlay.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $gsqp4$reactariauseModalOverlay = require("react-aria/useModalOverlay");
var $gsqp4$reactariaOverlay = require("react-aria/Overlay");
var $gsqp4$react = require("react");
var $gsqp4$reactariauseObjectRef = require("react-aria/useObjectRef");
var $gsqp4$reactariaprivateutilsuseViewportSize = require("react-aria/private/utils/useViewportSize");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Tray", function () { return $378dee1409fe2937$export$4589ed81930b555c; });
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











const $378dee1409fe2937$export$4589ed81930b555c = /*#__PURE__*/ (0, $gsqp4$react.forwardRef)(function Tray(props, ref) {
    let { children: children, state: state, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let wrapperRef = (0, $gsqp4$react.useRef)(null);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement((0, $906ecc59dea2a2ae$exports.Overlay), {
        ...otherProps,
        isOpen: state.isOpen,
        nodeRef: wrapperRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement($378dee1409fe2937$var$TrayWrapper, {
        ...props,
        wrapperRef: wrapperRef,
        ref: domRef
    }, children));
});
let $378dee1409fe2937$var$TrayWrapper = /*#__PURE__*/ (0, $gsqp4$react.forwardRef)(function(props, ref) {
    let { children: children, isOpen: isOpen, isFixedHeight: isFixedHeight, state: state, wrapperRef: wrapperRef } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let objRef = (0, $gsqp4$reactariauseObjectRef.useObjectRef)(ref);
    let { modalProps: modalProps, underlayProps: underlayProps } = (0, $gsqp4$reactariauseModalOverlay.useModalOverlay)({
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
    let viewport = (0, $gsqp4$reactariaprivateutilsuseViewportSize.useViewportSize)();
    let wrapperStyle = {
        '--spectrum-visual-viewport-height': viewport.height + 'px',
        // position: fixed elements are clipped by Safari on iOS 26, so we use
        // position: absolute and manually set the top to the scrollY.
        // The page can't scroll while the tray is open so this doesn't need to update.
        top: typeof window !== 'undefined' ? window.scrollY : 0
    };
    let wrapperClassName = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($b8a5afb76663131a$exports))), 'spectrum-Tray-wrapper');
    let className = (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($b8a5afb76663131a$exports))), 'spectrum-Tray', {
        'is-open': isOpen,
        'spectrum-Tray--fixedHeight': isFixedHeight
    }, (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($febdbd88af87631e$exports))), 'spectrum-Tray', 'react-spectrum-Tray'), styleProps.className);
    // Attach Transition's nodeRef to outer most wrapper for node.reflow: https://github.com/reactjs/react-transition-group/blob/c89f807067b32eea6f68fd6c622190d88ced82e2/src/Transition.js#L231
    return /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement("div", {
        ref: wrapperRef
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement((0, $1607bc090e03ac72$exports.Underlay), {
        ...underlayProps,
        isOpen: isOpen
    }), /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement("div", {
        className: wrapperClassName,
        style: wrapperStyle
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement("div", {
        ...styleProps,
        ...modalProps,
        className: className,
        ref: objRef,
        "data-testid": "tray"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement((0, $gsqp4$reactariaOverlay.DismissButton), {
        onDismiss: state.close
    }), children, /*#__PURE__*/ (0, ($parcel$interopDefault($gsqp4$react))).createElement((0, $gsqp4$reactariaOverlay.DismissButton), {
        onDismiss: state.close
    }))));
});


//# sourceMappingURL=Tray.cjs.map
