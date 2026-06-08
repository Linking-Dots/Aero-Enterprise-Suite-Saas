var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
require("../statuslight_vars.css");
var $31eb505cb7478e47$exports = require("../statuslight_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $bjVM1$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $bjVM1$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "StatusLight", function () { return $199e06528ee53086$export$5f84c37a31c6e41c; });
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






const $199e06528ee53086$export$5f84c37a31c6e41c = /*#__PURE__*/ (0, $bjVM1$react.forwardRef)(function StatusLight(props, ref) {
    let { variant: variant, children: children, isDisabled: isDisabled, role: role, ...otherProps } = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    if (!children && !props['aria-label'] && process.env.NODE_ENV !== 'production') console.warn('If no children are provided, an aria-label must be specified');
    if (!role && (props['aria-label'] || props['aria-labelledby']) && process.env.NODE_ENV !== 'production') console.warn('A labelled StatusLight must have a role.');
    return /*#__PURE__*/ (0, ($parcel$interopDefault($bjVM1$react))).createElement("div", {
        ...(0, $bjVM1$reactariafilterDOMProps.filterDOMProps)(otherProps, {
            labelable: !!role
        }),
        ...styleProps,
        role: role,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($31eb505cb7478e47$exports))), 'spectrum-StatusLight', `spectrum-StatusLight--${variant}`, {
            'is-disabled': isDisabled
        }, styleProps.className),
        ref: domRef
    }, children);
});


//# sourceMappingURL=StatusLight.cjs.map
