var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../well_vars.css");
var $a291c41c17a3e843$exports = require("../well_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $6NTgr$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $6NTgr$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Well", function () { return $8543fecce68d0b8e$export$d89c679ee2f73242; });
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





const $8543fecce68d0b8e$export$d89c679ee2f73242 = /*#__PURE__*/ (0, $6NTgr$react.forwardRef)(function Well(props, ref) {
    let { children: children, role: role, ...otherProps } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    if (!role && (props['aria-label'] || props['aria-labelledby']) && process.env.NODE_ENV !== 'production') console.warn('A labelled Well must have a role.');
    return /*#__PURE__*/ (0, ($parcel$interopDefault($6NTgr$react))).createElement("div", {
        ...(0, $6NTgr$reactariafilterDOMProps.filterDOMProps)(otherProps, {
            labelable: !!role
        }),
        ...styleProps,
        role: role,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($a291c41c17a3e843$exports))), 'spectrum-Well', styleProps.className)
    }, children);
});


//# sourceMappingURL=Well.cjs.map
