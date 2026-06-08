import {CheckboxGroupContext as $b27972722bd47f5e$export$baf37c4be89255b8} from "./context.js";
import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Field as $3967792f95357356$export$a455218a85c89869} from "../label/Field.js";
import {Provider as $089943c7a219141c$export$2881499e37b75b9a, useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import "../fieldgroup_vars.css";
import $1npp6$fieldgroup_vars_cssmjs from "../fieldgroup_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useCheckboxGroup as $1npp6$useCheckboxGroup} from "react-aria/useCheckboxGroup";
import $1npp6$react from "react";
import {useCheckboxGroupState as $1npp6$useCheckboxGroupState} from "react-stately/useCheckboxGroupState";


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









const $a060f1760eaa1b2e$export$4aa08d5625cb8ead = /*#__PURE__*/ (0, $1npp6$react).forwardRef(function CheckboxGroup(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let { isEmphasized: isEmphasized, children: children, orientation: orientation = 'vertical' } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let state = (0, $1npp6$useCheckboxGroupState)(props);
    let { groupProps: groupProps, ...otherProps } = (0, $1npp6$useCheckboxGroup)(props, state);
    return /*#__PURE__*/ (0, $1npp6$react).createElement((0, $3967792f95357356$export$a455218a85c89869), {
        ...props,
        ...otherProps,
        ref: domRef,
        wrapperClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1npp6$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup'),
        elementType: "span",
        includeNecessityIndicatorInAccessibilityName: true
    }, /*#__PURE__*/ (0, $1npp6$react).createElement("div", {
        ...groupProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($1npp6$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup-group', {
            'spectrum-FieldGroup-group--horizontal': orientation === 'horizontal'
        })
    }, /*#__PURE__*/ (0, $1npp6$react).createElement((0, $089943c7a219141c$export$2881499e37b75b9a), {
        isEmphasized: isEmphasized
    }, /*#__PURE__*/ (0, $1npp6$react).createElement((0, $b27972722bd47f5e$export$baf37c4be89255b8).Provider, {
        value: state
    }, children))));
});


export {$a060f1760eaa1b2e$export$4aa08d5625cb8ead as CheckboxGroup};
//# sourceMappingURL=CheckboxGroup.js.map
