var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $cb44ec0c6462cbc8$exports = require("./ProgressBarBase.cjs");
require("../barloader_vars.css");
var $f8f61250578b6123$exports = require("../barloader_vars_css.cjs");
var $7BDOY$react = require("react");
var $7BDOY$reactariauseProgressBar = require("react-aria/useProgressBar");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ProgressBar", function () { return $14c8def362a371e1$export$c17561cb55d4db30; });
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




const $14c8def362a371e1$export$c17561cb55d4db30 = /*#__PURE__*/ (0, ($parcel$interopDefault($7BDOY$react))).forwardRef(function ProgressBar(props, ref) {
    let { staticColor: staticColor, variant: variant, ...otherProps } = props;
    const { progressBarProps: progressBarProps, labelProps: labelProps } = (0, $7BDOY$reactariauseProgressBar.useProgressBar)(props);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($7BDOY$react))).createElement((0, $cb44ec0c6462cbc8$exports.ProgressBarBase), {
        ...otherProps,
        ref: ref,
        barProps: progressBarProps,
        labelProps: labelProps,
        barClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($f8f61250578b6123$exports))), {
            'spectrum-BarLoader--overBackground': variant === 'overBackground',
            'spectrum-BarLoader--staticWhite': staticColor === 'white',
            'spectrum-BarLoader--staticBlack': staticColor === 'black'
        })
    });
});


//# sourceMappingURL=ProgressBar.cjs.map
