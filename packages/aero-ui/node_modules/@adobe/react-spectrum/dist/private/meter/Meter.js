import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ProgressBarBase as $6e50fb064f124a35$export$7c6ed87244065f3a} from "../progress/ProgressBarBase.js";
import "../barloader_vars.css";
import $gjZR3$barloader_vars_cssmjs from "../barloader_vars_css.mjs";
import $gjZR3$react from "react";
import {useMeter as $gjZR3$useMeter} from "react-aria/useMeter";


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




const $a9930872c4fbddbb$export$62e3ae2a4090b879 = /*#__PURE__*/ (0, $gjZR3$react).forwardRef(function Meter(props, ref) {
    let { variant: variant = 'informative', ...otherProps } = props;
    const { meterProps: meterProps, labelProps: labelProps } = (0, $gjZR3$useMeter)(props);
    return /*#__PURE__*/ (0, $gjZR3$react).createElement((0, $6e50fb064f124a35$export$7c6ed87244065f3a), {
        ...otherProps,
        ref: ref,
        barProps: meterProps,
        labelProps: labelProps,
        barClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($gjZR3$barloader_vars_cssmjs))), {
            'is-positive': variant === 'positive',
            'is-warning': variant === 'warning',
            'is-critical': variant === 'critical'
        })
    });
});


export {$a9930872c4fbddbb$export$62e3ae2a4090b879 as Meter};
//# sourceMappingURL=Meter.js.map
