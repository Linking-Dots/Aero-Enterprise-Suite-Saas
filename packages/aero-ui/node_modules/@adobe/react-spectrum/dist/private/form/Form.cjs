var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
require("../fieldlabel_vars.css");
var $53185441bef09fa8$exports = require("../fieldlabel_vars_css.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $b8f90d51c4908137$exports = require("../utils/styleProps.cjs");
var $hOaLg$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $hOaLg$reactstatelyprivateformuseFormValidationState = require("react-stately/private/form/useFormValidationState");
var $hOaLg$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "useFormProps", function () { return $1af2ca8553741739$export$a6b5be5c6b451665; });
$parcel$export(module.exports, "Form", function () { return $1af2ca8553741739$export$a7fed597f4b8afd8; });
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







let $1af2ca8553741739$var$FormContext = /*#__PURE__*/ (0, ($parcel$interopDefault($hOaLg$react))).createContext(null);
function $1af2ca8553741739$export$a6b5be5c6b451665(props) {
    let ctx = (0, $hOaLg$react.useContext)($1af2ca8553741739$var$FormContext);
    if (ctx) return {
        ...ctx,
        ...props
    };
    return props;
}
const $1af2ca8553741739$var$formPropNames = new Set([
    'action',
    'autoComplete',
    'encType',
    'method',
    'target',
    'onSubmit',
    'onReset',
    'onInvalid'
]);
const $1af2ca8553741739$export$a7fed597f4b8afd8 = /*#__PURE__*/ (0, ($parcel$interopDefault($hOaLg$react))).forwardRef(function Form(props, ref) {
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    let { children: children, labelPosition: labelPosition = 'top', labelAlign: labelAlign = 'start', isRequired: isRequired, necessityIndicator: necessityIndicator, isQuiet: isQuiet, isEmphasized: isEmphasized, isDisabled: isDisabled, isReadOnly: isReadOnly, validationState: validationState, validationBehavior: validationBehavior, validationErrors: validationErrors, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $b8f90d51c4908137$exports.useStyleProps)(otherProps);
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    let ctx = {
        labelPosition: labelPosition,
        labelAlign: labelAlign,
        necessityIndicator: necessityIndicator,
        validationBehavior: validationBehavior
    };
    return /*#__PURE__*/ (0, ($parcel$interopDefault($hOaLg$react))).createElement("form", {
        ...(0, $hOaLg$reactariafilterDOMProps.filterDOMProps)(otherProps, {
            labelable: true,
            propNames: $1af2ca8553741739$var$formPropNames
        }),
        ...styleProps,
        noValidate: validationBehavior !== 'native',
        ref: domRef,
        className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($53185441bef09fa8$exports))), 'spectrum-Form', {
            'spectrum-Form--positionSide': labelPosition === 'side',
            'spectrum-Form--positionTop': labelPosition === 'top'
        }, styleProps.className)
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hOaLg$react))).createElement($1af2ca8553741739$var$FormContext.Provider, {
        value: ctx
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hOaLg$react))).createElement((0, $544fc82701fc93e9$exports.Provider), {
        isQuiet: isQuiet,
        isEmphasized: isEmphasized,
        isDisabled: isDisabled,
        isReadOnly: isReadOnly,
        isRequired: isRequired,
        validationState: validationState
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hOaLg$react))).createElement((0, $hOaLg$reactstatelyprivateformuseFormValidationState.FormValidationContext).Provider, {
        value: validationErrors || {}
    }, children))));
});


//# sourceMappingURL=Form.cjs.map
