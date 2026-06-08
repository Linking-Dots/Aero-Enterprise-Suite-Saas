import {CheckboxGroupContext as $f8719a07416b8330$export$baf37c4be89255b8} from "./context.mjs";
import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Field as $adcd096854d27620$export$a455218a85c89869} from "../label/Field.mjs";
import {Provider as $71dfb0e0358a12de$export$2881499e37b75b9a, useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import "../fieldgroup_vars.css";
import $lcyaT$fieldgroup_vars_cssmjs from "../fieldgroup_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useCheckboxGroup as $lcyaT$useCheckboxGroup} from "react-aria/useCheckboxGroup";
import $lcyaT$react from "react";
import {useCheckboxGroupState as $lcyaT$useCheckboxGroupState} from "react-stately/useCheckboxGroupState";


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









const $b03f778683a7c333$export$4aa08d5625cb8ead = /*#__PURE__*/ (0, $lcyaT$react).forwardRef(function CheckboxGroup(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let { isEmphasized: isEmphasized, children: children, orientation: orientation = 'vertical' } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let state = (0, $lcyaT$useCheckboxGroupState)(props);
    let { groupProps: groupProps, ...otherProps } = (0, $lcyaT$useCheckboxGroup)(props, state);
    return /*#__PURE__*/ (0, $lcyaT$react).createElement((0, $adcd096854d27620$export$a455218a85c89869), {
        ...props,
        ...otherProps,
        ref: domRef,
        wrapperClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lcyaT$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup'),
        elementType: "span",
        includeNecessityIndicatorInAccessibilityName: true
    }, /*#__PURE__*/ (0, $lcyaT$react).createElement("div", {
        ...groupProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lcyaT$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup-group', {
            'spectrum-FieldGroup-group--horizontal': orientation === 'horizontal'
        })
    }, /*#__PURE__*/ (0, $lcyaT$react).createElement((0, $71dfb0e0358a12de$export$2881499e37b75b9a), {
        isEmphasized: isEmphasized
    }, /*#__PURE__*/ (0, $lcyaT$react).createElement((0, $f8719a07416b8330$export$baf37c4be89255b8).Provider, {
        value: state
    }, children))));
});


export {$b03f778683a7c333$export$4aa08d5625cb8ead as CheckboxGroup};
//# sourceMappingURL=CheckboxGroup.mjs.map
