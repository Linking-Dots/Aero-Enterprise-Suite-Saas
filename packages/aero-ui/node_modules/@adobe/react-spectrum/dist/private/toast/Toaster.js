import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Provider as $089943c7a219141c$export$2881499e37b75b9a} from "../provider/Provider.js";
import "./toastContainer.css";
import $1ejEg$toastContainer_cssmjs from "./toastContainer_css.mjs";
import {useToastRegion as $1ejEg$useToastRegion} from "react-aria/useToast";
import {FocusScope as $1ejEg$FocusScope} from "react-aria/FocusScope";
import {mergeProps as $1ejEg$mergeProps} from "react-aria/mergeProps";
import $1ejEg$react, {createContext as $1ejEg$createContext, useRef as $1ejEg$useRef, useMemo as $1ejEg$useMemo} from "react";
import $1ejEg$reactdom from "react-dom";
import {useFocusRing as $1ejEg$useFocusRing} from "react-aria/useFocusRing";
import {useUNSAFE_PortalContext as $1ejEg$useUNSAFE_PortalContext} from "react-aria/PortalProvider";


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









const $cf5b35630850d4e0$export$9194c0aa0cd7a9ff = /*#__PURE__*/ (0, $1ejEg$createContext)(false);
function $cf5b35630850d4e0$export$fb98e3a2a4cd92d7(props) {
    let { children: children, state: state } = props;
    let ref = (0, $1ejEg$useRef)(null);
    let { regionProps: regionProps } = (0, $1ejEg$useToastRegion)(props, state, ref);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $1ejEg$useFocusRing)();
    let { getContainer: getContainer } = (0, $1ejEg$useUNSAFE_PortalContext)();
    let [position, placement] = (0, $1ejEg$useMemo)(()=>{
        var _props_placement;
        let [pos = 'bottom', place = 'center'] = ((_props_placement = props.placement) === null || _props_placement === void 0 ? void 0 : _props_placement.split(' ')) || [];
        return [
            pos,
            place
        ];
    }, [
        props.placement
    ]);
    let contents = /*#__PURE__*/ (0, $1ejEg$react).createElement((0, $089943c7a219141c$export$2881499e37b75b9a), {
        UNSAFE_style: {
            background: 'transparent'
        }
    }, /*#__PURE__*/ (0, $1ejEg$react).createElement((0, $1ejEg$FocusScope), null, /*#__PURE__*/ (0, $1ejEg$react).createElement($cf5b35630850d4e0$export$9194c0aa0cd7a9ff.Provider, {
        value: isFocusVisible
    }, /*#__PURE__*/ (0, $1ejEg$react).createElement("div", {
        ...(0, $1ejEg$mergeProps)(regionProps, focusProps),
        ref: ref,
        "data-position": position,
        "data-placement": placement,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1ejEg$toastContainer_cssmjs))), 'react-spectrum-ToastContainer', {
            'focus-ring': isFocusVisible
        })
    }, children))));
    var _getContainer;
    return /*#__PURE__*/ (0, $1ejEg$reactdom).createPortal(contents, (_getContainer = getContainer === null || getContainer === void 0 ? void 0 : getContainer()) !== null && _getContainer !== void 0 ? _getContainer : document.body);
}


export {$cf5b35630850d4e0$export$9194c0aa0cd7a9ff as ToasterContext, $cf5b35630850d4e0$export$fb98e3a2a4cd92d7 as Toaster};
//# sourceMappingURL=Toaster.js.map
