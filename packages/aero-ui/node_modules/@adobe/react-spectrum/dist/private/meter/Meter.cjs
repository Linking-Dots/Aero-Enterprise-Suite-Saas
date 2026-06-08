var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $cb44ec0c6462cbc8$exports = require("../progress/ProgressBarBase.cjs");
require("../barloader_vars.css");
var $f8f61250578b6123$exports = require("../barloader_vars_css.cjs");
var $iwPvJ$react = require("react");
var $iwPvJ$reactariauseMeter = require("react-aria/useMeter");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "Meter", function () { return $635962780a915a6c$export$62e3ae2a4090b879; });
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




const $635962780a915a6c$export$62e3ae2a4090b879 = /*#__PURE__*/ (0, ($parcel$interopDefault($iwPvJ$react))).forwardRef(function Meter(props, ref) {
    let { variant: variant = 'informative', ...otherProps } = props;
    const { meterProps: meterProps, labelProps: labelProps } = (0, $iwPvJ$reactariauseMeter.useMeter)(props);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($iwPvJ$react))).createElement((0, $cb44ec0c6462cbc8$exports.ProgressBarBase), {
        ...otherProps,
        ref: ref,
        barProps: meterProps,
        labelProps: labelProps,
        barClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($f8f61250578b6123$exports))), {
            'is-positive': variant === 'positive',
            'is-warning': variant === 'warning',
            'is-critical': variant === 'critical'
        })
    });
});


//# sourceMappingURL=Meter.cjs.map
