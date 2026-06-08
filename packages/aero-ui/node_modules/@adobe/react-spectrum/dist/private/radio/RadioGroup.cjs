var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $b93966d678e0af07$exports = require("../label/Field.cjs");
var $8ff76ba65921a904$exports = require("./context.cjs");
require("../fieldgroup_vars.css");
var $215ba4e4fd98bf88$exports = require("../fieldgroup_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $1af2ca8553741739$exports = require("../form/Form.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $8owBq$reactariauseRadioGroup = require("react-aria/useRadioGroup");
var $8owBq$react = require("react");
var $8owBq$reactstatelyuseRadioGroupState = require("react-stately/useRadioGroupState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "RadioGroup", function () { return $9873f6a3230cc089$export$a98f0dcb43a68a25; });
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









const $9873f6a3230cc089$export$a98f0dcb43a68a25 = /*#__PURE__*/ (0, ($parcel$interopDefault($8owBq$react))).forwardRef(function RadioGroup(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $1af2ca8553741739$exports.useFormProps)(props);
    let { isEmphasized: isEmphasized, children: children, orientation: orientation = 'vertical' } = props;
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let state = (0, $8owBq$reactstatelyuseRadioGroupState.useRadioGroupState)(props);
    let { radioGroupProps: radioGroupProps, ...otherProps } = (0, $8owBq$reactariauseRadioGroup.useRadioGroup)(props, state);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($8owBq$react))).createElement((0, $b93966d678e0af07$exports.Field), {
        ...props,
        ...otherProps,
        ref: domRef,
        wrapperClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($215ba4e4fd98bf88$exports))), 'spectrum-FieldGroup'),
        elementType: "span"
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8owBq$react))).createElement("div", {
        ...radioGroupProps,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($215ba4e4fd98bf88$exports))), 'spectrum-FieldGroup-group', {
            'spectrum-FieldGroup-group--horizontal': orientation === 'horizontal'
        })
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($8owBq$react))).createElement((0, $8ff76ba65921a904$exports.RadioContext).Provider, {
        value: {
            isEmphasized: isEmphasized,
            state: state
        }
    }, children)));
});


//# sourceMappingURL=RadioGroup.cjs.map
