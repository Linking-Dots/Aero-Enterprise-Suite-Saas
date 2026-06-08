var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
require("./toastContainer.css");
var $1e451ff201076fe2$exports = require("./toastContainer_css.cjs");
var $azwgK$reactariauseToast = require("react-aria/useToast");
var $azwgK$reactariaFocusScope = require("react-aria/FocusScope");
var $azwgK$reactariamergeProps = require("react-aria/mergeProps");
var $azwgK$react = require("react");
var $azwgK$reactdom = require("react-dom");
var $azwgK$reactariauseFocusRing = require("react-aria/useFocusRing");
var $azwgK$reactariaPortalProvider = require("react-aria/PortalProvider");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Toaster", function () { return $d20d60b56e209593$export$fb98e3a2a4cd92d7; });
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









const $d20d60b56e209593$export$9194c0aa0cd7a9ff = /*#__PURE__*/ (0, $azwgK$react.createContext)(false);
function $d20d60b56e209593$export$fb98e3a2a4cd92d7(props) {
    let { children: children, state: state } = props;
    let ref = (0, $azwgK$react.useRef)(null);
    let { regionProps: regionProps } = (0, $azwgK$reactariauseToast.useToastRegion)(props, state, ref);
    let { focusProps: focusProps, isFocusVisible: isFocusVisible } = (0, $azwgK$reactariauseFocusRing.useFocusRing)();
    let { getContainer: getContainer } = (0, $azwgK$reactariaPortalProvider.useUNSAFE_PortalContext)();
    let [position, placement] = (0, $azwgK$react.useMemo)(()=>{
        let [pos = 'bottom', place = 'center'] = props.placement?.split(' ') || [];
        return [
            pos,
            place
        ];
    }, [
        props.placement
    ]);
    let contents = /*#__PURE__*/ (0, ($parcel$interopDefault($azwgK$react))).createElement((0, $544fc82701fc93e9$exports.Provider), {
        UNSAFE_style: {
            background: 'transparent'
        }
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($azwgK$react))).createElement((0, $azwgK$reactariaFocusScope.FocusScope), null, /*#__PURE__*/ (0, ($parcel$interopDefault($azwgK$react))).createElement($d20d60b56e209593$export$9194c0aa0cd7a9ff.Provider, {
        value: isFocusVisible
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($azwgK$react))).createElement("div", {
        ...(0, $azwgK$reactariamergeProps.mergeProps)(regionProps, focusProps),
        ref: ref,
        "data-position": position,
        "data-placement": placement,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($1e451ff201076fe2$exports))), 'react-spectrum-ToastContainer', {
            'focus-ring': isFocusVisible
        })
    }, children))));
    return /*#__PURE__*/ (0, ($parcel$interopDefault($azwgK$reactdom))).createPortal(contents, getContainer?.() ?? document.body);
}


//# sourceMappingURL=Toaster.cjs.map
