var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $6f2b983372959298$exports = require("./StepListContext.cjs");
var $712472d83922a2e9$exports = require("./StepListItem.cjs");
require("../steplist_vars.css");
var $d97c8cb44f9e179c$exports = require("../steplist_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $cMVAb$reactariaprivatesteplistuseStepList = require("react-aria/private/steplist/useStepList");
var $cMVAb$react = require("react");
var $cMVAb$reactstatelyprivatesteplistuseStepListState = require("react-stately/private/steplist/useStepListState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "StepList", function () { return $cb4ca86cee412d58$export$ff2e09ca3ba758a9; });
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









const $cb4ca86cee412d58$export$ff2e09ca3ba758a9 = /*#__PURE__*/ (0, ($parcel$interopDefault($cMVAb$react))).forwardRef(function StepList(props, ref) {
    const { size: size = 'M', orientation: orientation = 'horizontal' } = props;
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    const { isDisabled: isDisabled, isEmphasized: isEmphasized } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(props);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let state = (0, $cMVAb$reactstatelyprivatesteplistuseStepListState.useStepListState)(props);
    let { listProps: listProps } = (0, $cMVAb$reactariaprivatesteplistuseStepList.useStepList)(props, state, domRef);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($cMVAb$react))).createElement("ol", {
        ...listProps,
        ...styleProps,
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d97c8cb44f9e179c$exports))), 'spectrum-Steplist', styleProps.className, {
            'spectrum-Steplist--small': size === 'S',
            'spectrum-Steplist--medium': size === 'M',
            'spectrum-Steplist--large': size === 'L',
            'spectrum-Steplist--xlarge': size === 'XL',
            'spectrum-Steplist--emphasized': isEmphasized,
            'spectrum-Steplist--horizontal': orientation === 'horizontal',
            'spectrum-Steplist--vertical': orientation === 'vertical'
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($cMVAb$react))).createElement((0, $6f2b983372959298$exports.StepListContext).Provider, {
        value: state
    }, [
        ...state.collection
    ].map((item)=>/*#__PURE__*/ (0, ($parcel$interopDefault($cMVAb$react))).createElement((0, $712472d83922a2e9$exports.StepListItem), {
            key: item.key,
            isDisabled: isDisabled,
            item: item
        }))));
});


//# sourceMappingURL=StepList.cjs.map
