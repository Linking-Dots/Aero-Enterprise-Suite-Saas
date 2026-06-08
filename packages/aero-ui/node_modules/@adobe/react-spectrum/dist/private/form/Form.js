import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Provider as $089943c7a219141c$export$2881499e37b75b9a, useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import "../fieldlabel_vars.css";
import $lj4dW$fieldlabel_vars_cssmjs from "../fieldlabel_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useStyleProps as $120fbea2d95e11ed$export$b8e6fb9d2dff3f41} from "../utils/styleProps.js";
import {filterDOMProps as $lj4dW$filterDOMProps} from "react-aria/filterDOMProps";
import {FormValidationContext as $lj4dW$FormValidationContext} from "react-stately/private/form/useFormValidationState";
import $lj4dW$react, {useContext as $lj4dW$useContext} from "react";


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







let $d23ca6800ac02cf1$var$FormContext = /*#__PURE__*/ (0, $lj4dW$react).createContext(null);
function $d23ca6800ac02cf1$export$a6b5be5c6b451665(props) {
    let ctx = (0, $lj4dW$useContext)($d23ca6800ac02cf1$var$FormContext);
    if (ctx) return {
        ...ctx,
        ...props
    };
    return props;
}
const $d23ca6800ac02cf1$var$formPropNames = new Set([
    'action',
    'autoComplete',
    'encType',
    'method',
    'target',
    'onSubmit',
    'onReset',
    'onInvalid'
]);
const $d23ca6800ac02cf1$export$a7fed597f4b8afd8 = /*#__PURE__*/ (0, $lj4dW$react).forwardRef(function Form(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    let { children: children, labelPosition: labelPosition = 'top', labelAlign: labelAlign = 'start', isRequired: isRequired, necessityIndicator: necessityIndicator, isQuiet: isQuiet, isEmphasized: isEmphasized, isDisabled: isDisabled, isReadOnly: isReadOnly, validationState: validationState, validationBehavior: validationBehavior, validationErrors: validationErrors, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $120fbea2d95e11ed$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let ctx = {
        labelPosition: labelPosition,
        labelAlign: labelAlign,
        necessityIndicator: necessityIndicator,
        validationBehavior: validationBehavior
    };
    return /*#__PURE__*/ (0, $lj4dW$react).createElement("form", {
        ...(0, $lj4dW$filterDOMProps)(otherProps, {
            labelable: true,
            propNames: $d23ca6800ac02cf1$var$formPropNames
        }),
        ...styleProps,
        noValidate: validationBehavior !== 'native',
        ref: domRef,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lj4dW$fieldlabel_vars_cssmjs))), 'spectrum-Form', {
            'spectrum-Form--positionSide': labelPosition === 'side',
            'spectrum-Form--positionTop': labelPosition === 'top'
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $lj4dW$react).createElement($d23ca6800ac02cf1$var$FormContext.Provider, {
        value: ctx
    }, /*#__PURE__*/ (0, $lj4dW$react).createElement((0, $089943c7a219141c$export$2881499e37b75b9a), {
        isQuiet: isQuiet,
        isEmphasized: isEmphasized,
        isDisabled: isDisabled,
        isReadOnly: isReadOnly,
        isRequired: isRequired,
        validationState: validationState
    }, /*#__PURE__*/ (0, $lj4dW$react).createElement((0, $lj4dW$FormValidationContext).Provider, {
        value: validationErrors || {}
    }, children))));
});


export {$d23ca6800ac02cf1$export$a6b5be5c6b451665 as useFormProps, $d23ca6800ac02cf1$export$a7fed597f4b8afd8 as Form};
//# sourceMappingURL=Form.js.map
