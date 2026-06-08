import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ProgressBarBase as $4b8319cd7a745a76$export$7c6ed87244065f3a} from "../progress/ProgressBarBase.mjs";
import "../barloader_vars.css";
import $99Vmc$barloader_vars_cssmjs from "../barloader_vars_css.mjs";
import $99Vmc$react from "react";
import {useMeter as $99Vmc$useMeter} from "react-aria/useMeter";


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




const $942109bbdf7edccb$export$62e3ae2a4090b879 = /*#__PURE__*/ (0, $99Vmc$react).forwardRef(function Meter(props, ref) {
    let { variant: variant = 'informative', ...otherProps } = props;
    const { meterProps: meterProps, labelProps: labelProps } = (0, $99Vmc$useMeter)(props);
    return /*#__PURE__*/ (0, $99Vmc$react).createElement((0, $4b8319cd7a745a76$export$7c6ed87244065f3a), {
        ...otherProps,
        ref: ref,
        barProps: meterProps,
        labelProps: labelProps,
        barClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($99Vmc$barloader_vars_cssmjs))), {
            'is-positive': variant === 'positive',
            'is-warning': variant === 'warning',
            'is-critical': variant === 'critical'
        })
    });
});


export {$942109bbdf7edccb$export$62e3ae2a4090b879 as Meter};
//# sourceMappingURL=Meter.mjs.map
