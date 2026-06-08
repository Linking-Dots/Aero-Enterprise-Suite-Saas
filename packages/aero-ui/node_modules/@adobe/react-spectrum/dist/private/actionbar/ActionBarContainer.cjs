var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("./actionbar.css");
var $b462d0874eb51e82$exports = require("./actionbar_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $eKGSC$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $eKGSC$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ActionBarContainer", function () { return $d0cab84b0849ac6d$export$ac2eb07f267e434c; });
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






const $d0cab84b0849ac6d$export$ac2eb07f267e434c = /*#__PURE__*/ (0, ($parcel$interopDefault($eKGSC$react))).forwardRef(function ActionBarContainer(props, ref) {
    // Grabs specific props from the closest Provider (see https://react-spectrum.adobe.com/react-spectrum/Provider.html#property-groups). Remove if your component doesn't support any of the listed props.
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { children: children } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($eKGSC$react))).createElement("div", {
        ...(0, $eKGSC$reactariafilterDOMProps.filterDOMProps)(props),
        ...styleProps,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($b462d0874eb51e82$exports))), 'ActionBarContainer', styleProps.className)
    }, children);
});


//# sourceMappingURL=ActionBarContainer.cjs.map
