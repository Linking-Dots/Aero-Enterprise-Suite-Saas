import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {StepListContext as $bfe1dd9c11a62094$export$66136572efa4af6e} from "./StepListContext.mjs";
import {StepListItem as $49159f98bfb552a9$export$87c2a8a94eda1754} from "./StepListItem.mjs";
import "../steplist_vars.css";
import $9CXwW$steplist_vars_cssmjs from "../steplist_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {useStepList as $9CXwW$useStepList} from "react-aria/private/steplist/useStepList";
import $9CXwW$react from "react";
import {useStepListState as $9CXwW$useStepListState} from "react-stately/private/steplist/useStepListState";


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









const $38e065fa2c42f42e$export$ff2e09ca3ba758a9 = /*#__PURE__*/ (0, $9CXwW$react).forwardRef(function StepList(props, ref) {
    const { size: size = 'M', orientation: orientation = 'horizontal' } = props;
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    const { isDisabled: isDisabled, isEmphasized: isEmphasized } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(props);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let state = (0, $9CXwW$useStepListState)(props);
    let { listProps: listProps } = (0, $9CXwW$useStepList)(props, state, domRef);
    return /*#__PURE__*/ (0, $9CXwW$react).createElement("ol", {
        ...listProps,
        ...styleProps,
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($9CXwW$steplist_vars_cssmjs))), 'spectrum-Steplist', styleProps.className, {
            'spectrum-Steplist--small': size === 'S',
            'spectrum-Steplist--medium': size === 'M',
            'spectrum-Steplist--large': size === 'L',
            'spectrum-Steplist--xlarge': size === 'XL',
            'spectrum-Steplist--emphasized': isEmphasized,
            'spectrum-Steplist--horizontal': orientation === 'horizontal',
            'spectrum-Steplist--vertical': orientation === 'vertical'
        })
    }, /*#__PURE__*/ (0, $9CXwW$react).createElement((0, $bfe1dd9c11a62094$export$66136572efa4af6e).Provider, {
        value: state
    }, [
        ...state.collection
    ].map((item)=>/*#__PURE__*/ (0, $9CXwW$react).createElement((0, $49159f98bfb552a9$export$87c2a8a94eda1754), {
            key: item.key,
            isDisabled: isDisabled,
            item: item
        }))));
});


export {$38e065fa2c42f42e$export$ff2e09ca3ba758a9 as StepList};
//# sourceMappingURL=StepList.mjs.map
