import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Provider as $71dfb0e0358a12de$export$2881499e37b75b9a} from "../provider/Provider.mjs";
import "./toastContainer.css";
import $c5YWZ$toastContainer_cssmjs from "./toastContainer_css.mjs";
import {useToastRegion as $c5YWZ$useToastRegion} from "react-aria/useToast";
import {FocusScope as $c5YWZ$FocusScope} from "react-aria/FocusScope";
import {mergeProps as $c5YWZ$mergeProps} from "react-aria/mergeProps";
import $c5YWZ$react, {createContext as $c5YWZ$createContext, useRef as $c5YWZ$useRef, useMemo as $c5YWZ$useMemo} from "react";
import $c5YWZ$reactdom from "react-dom";
import {useFocusRing as $c5YWZ$useFocusRing} from "react-aria/useFocusRing";
import {useUNSAFE_PortalContext as $c5YWZ$useUNSAFE_PortalContext} from "react-aria/PortalProvider";


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









const $74a94cdc2e765763$export$9194c0aa0cd7a9ff = /*#__PURE__*/ (0, $c5YWZ$createContext)(false);
function $74a94cdc2e765763$export$fb98e3a2a4cd92d7(props) {
    let { children: children, state: state } = props;
    let ref = (0, $c5YWZ$useRef)(null);
    let { regionProps: regionProps } = (0, $c5YWZ$useToastRegion)(props, state, ref);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $c5YWZ$useFocusRing)();
    let { getContainer: getContainer } = (0, $c5YWZ$useUNSAFE_PortalContext)();
    let [position, placement] = (0, $c5YWZ$useMemo)(()=>{
        let [pos = 'bottom', place = 'center'] = props.placement?.split(' ') || [];
        return [
            pos,
            place
        ];
    }, [
        props.placement
    ]);
    let contents = /*#__PURE__*/ (0, $c5YWZ$react).createElement((0, $71dfb0e0358a12de$export$2881499e37b75b9a), {
        UNSAFE_style: {
            background: 'transparent'
        }
    }, /*#__PURE__*/ (0, $c5YWZ$react).createElement((0, $c5YWZ$FocusScope), null, /*#__PURE__*/ (0, $c5YWZ$react).createElement($74a94cdc2e765763$export$9194c0aa0cd7a9ff.Provider, {
        value: isFocusVisible
    }, /*#__PURE__*/ (0, $c5YWZ$react).createElement("div", {
        ...(0, $c5YWZ$mergeProps)(regionProps, focusProps),
        ref: ref,
        "data-position": position,
        "data-placement": placement,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($c5YWZ$toastContainer_cssmjs))), 'react-spectrum-ToastContainer', {
            'focus-ring': isFocusVisible
        })
    }, children))));
    return /*#__PURE__*/ (0, $c5YWZ$reactdom).createPortal(contents, getContainer?.() ?? document.body);
}


export {$74a94cdc2e765763$export$9194c0aa0cd7a9ff as ToasterContext, $74a94cdc2e765763$export$fb98e3a2a4cd92d7 as Toaster};
//# sourceMappingURL=Toaster.mjs.map
