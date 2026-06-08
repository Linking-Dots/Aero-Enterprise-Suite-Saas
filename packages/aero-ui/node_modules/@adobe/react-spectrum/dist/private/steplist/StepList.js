import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {StepListContext as $9831a32a4a052188$export$66136572efa4af6e} from "./StepListContext.js";
import {StepListItem as $84f7e0d248669720$export$87c2a8a94eda1754} from "./StepListItem.js";
import "../steplist_vars.css";
import $k1LML$steplist_vars_cssmjs from "../steplist_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {useStepList as $k1LML$useStepList} from "react-aria/private/steplist/useStepList";
import $k1LML$react from "react";
import {useStepListState as $k1LML$useStepListState} from "react-stately/private/steplist/useStepListState";


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 









const $36204131a7d6011f$export$ff2e09ca3ba758a9 = /*#__PURE__*/ (0, $k1LML$react).forwardRef(function StepList(props, ref) {
    const { size: size = 'M', orientation: orientation = 'horizontal' } = props;
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    const { isDisabled: isDisabled, isEmphasized: isEmphasized } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let state = (0, $k1LML$useStepListState)(props);
    let { listProps: listProps } = (0, $k1LML$useStepList)(props, state, domRef);
    return /*#__PURE__*/ (0, $k1LML$react).createElement("ol", {
        ...listProps,
        ...styleProps,
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($k1LML$steplist_vars_cssmjs))), 'spectrum-Steplist', styleProps.className, {
            'spectrum-Steplist--small': size === 'S',
            'spectrum-Steplist--medium': size === 'M',
            'spectrum-Steplist--large': size === 'L',
            'spectrum-Steplist--xlarge': size === 'XL',
            'spectrum-Steplist--emphasized': isEmphasized,
            'spectrum-Steplist--horizontal': orientation === 'horizontal',
            'spectrum-Steplist--vertical': orientation === 'vertical'
        })
    }, /*#__PURE__*/ (0, $k1LML$react).createElement((0, $9831a32a4a052188$export$66136572efa4af6e).Provider, {
        value: state
    }, [
        ...state.collection
    ].map((item)=>/*#__PURE__*/ (0, $k1LML$react).createElement((0, $84f7e0d248669720$export$87c2a8a94eda1754), {
            key: item.key,
            isDisabled: isDisabled,
            item: item
        }))));
});


export {$36204131a7d6011f$export$ff2e09ca3ba758a9 as StepList};
//# sourceMappingURL=StepList.js.map
