import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ProgressBarBase as $4b8319cd7a745a76$export$7c6ed87244065f3a} from "./ProgressBarBase.mjs";
import "../barloader_vars.css";
import $eEdDV$barloader_vars_cssmjs from "../barloader_vars_css.mjs";
import $eEdDV$react from "react";
import {useProgressBar as $eEdDV$useProgressBar} from "react-aria/useProgressBar";


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




const $89c90cef022a5bf5$export$c17561cb55d4db30 = /*#__PURE__*/ (0, $eEdDV$react).forwardRef(function ProgressBar(props, ref) {
    let { staticColor: staticColor, variant: variant, ...otherProps } = props;
    const { progressBarProps: progressBarProps, labelProps: labelProps } = (0, $eEdDV$useProgressBar)(props);
    return /*#__PURE__*/ (0, $eEdDV$react).createElement((0, $4b8319cd7a745a76$export$7c6ed87244065f3a), {
        ...otherProps,
        ref: ref,
        barProps: progressBarProps,
        labelProps: labelProps,
        barClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($eEdDV$barloader_vars_cssmjs))), {
            'spectrum-BarLoader--overBackground': variant === 'overBackground',
            'spectrum-BarLoader--staticWhite': staticColor === 'white',
            'spectrum-BarLoader--staticBlack': staticColor === 'black'
        })
    });
});


export {$89c90cef022a5bf5$export$c17561cb55d4db30 as ProgressBar};
//# sourceMappingURL=ProgressBar.mjs.map
