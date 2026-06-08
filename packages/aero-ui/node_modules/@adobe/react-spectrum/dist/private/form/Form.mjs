import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Provider as $71dfb0e0358a12de$export$2881499e37b75b9a, useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import "../fieldlabel_vars.css";
import $iQf3c$fieldlabel_vars_cssmjs from "../fieldlabel_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useStyleProps as $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41} from "../utils/styleProps.mjs";
import {filterDOMProps as $iQf3c$filterDOMProps} from "react-aria/filterDOMProps";
import {FormValidationContext as $iQf3c$FormValidationContext} from "react-stately/private/form/useFormValidationState";
import $iQf3c$react, {useContext as $iQf3c$useContext} from "react";


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







let $c29c48d4ef19ffc4$var$FormContext = /*#__PURE__*/ (0, $iQf3c$react).createContext(null);
function $c29c48d4ef19ffc4$export$a6b5be5c6b451665(props) {
    let ctx = (0, $iQf3c$useContext)($c29c48d4ef19ffc4$var$FormContext);
    if (ctx) return {
        ...ctx,
        ...props
    };
    return props;
}
const $c29c48d4ef19ffc4$var$formPropNames = new Set([
    'action',
    'autoComplete',
    'encType',
    'method',
    'target',
    'onSubmit',
    'onReset',
    'onInvalid'
]);
const $c29c48d4ef19ffc4$export$a7fed597f4b8afd8 = /*#__PURE__*/ (0, $iQf3c$react).forwardRef(function Form(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    let { children: children, labelPosition: labelPosition = 'top', labelAlign: labelAlign = 'start', isRequired: isRequired, necessityIndicator: necessityIndicator, isQuiet: isQuiet, isEmphasized: isEmphasized, isDisabled: isDisabled, isReadOnly: isReadOnly, validationState: validationState, validationBehavior: validationBehavior, validationErrors: validationErrors, ...otherProps } = props;
    let { styleProps: styleProps } = (0, $63d03c54ca5e4b88$export$b8e6fb9d2dff3f41)(otherProps);
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let ctx = {
        labelPosition: labelPosition,
        labelAlign: labelAlign,
        necessityIndicator: necessityIndicator,
        validationBehavior: validationBehavior
    };
    return /*#__PURE__*/ (0, $iQf3c$react).createElement("form", {
        ...(0, $iQf3c$filterDOMProps)(otherProps, {
            labelable: true,
            propNames: $c29c48d4ef19ffc4$var$formPropNames
        }),
        ...styleProps,
        noValidate: validationBehavior !== 'native',
        ref: domRef,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($iQf3c$fieldlabel_vars_cssmjs))), 'spectrum-Form', {
            'spectrum-Form--positionSide': labelPosition === 'side',
            'spectrum-Form--positionTop': labelPosition === 'top'
        }, styleProps.className)
    }, /*#__PURE__*/ (0, $iQf3c$react).createElement($c29c48d4ef19ffc4$var$FormContext.Provider, {
        value: ctx
    }, /*#__PURE__*/ (0, $iQf3c$react).createElement((0, $71dfb0e0358a12de$export$2881499e37b75b9a), {
        isQuiet: isQuiet,
        isEmphasized: isEmphasized,
        isDisabled: isDisabled,
        isReadOnly: isReadOnly,
        isRequired: isRequired,
        validationState: validationState
    }, /*#__PURE__*/ (0, $iQf3c$react).createElement((0, $iQf3c$FormValidationContext).Provider, {
        value: validationErrors || {}
    }, children))));
});


export {$c29c48d4ef19ffc4$export$a6b5be5c6b451665 as useFormProps, $c29c48d4ef19ffc4$export$a7fed597f4b8afd8 as Form};
//# sourceMappingURL=Form.mjs.map
