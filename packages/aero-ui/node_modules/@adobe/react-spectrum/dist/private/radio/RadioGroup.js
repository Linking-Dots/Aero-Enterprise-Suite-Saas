import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {Field as $3967792f95357356$export$a455218a85c89869} from "../label/Field.js";
import {RadioContext as $d94927a7c7b6e45d$export$b118023277d4a5c3} from "./context.js";
import "../fieldgroup_vars.css";
import $jmxtg$fieldgroup_vars_cssmjs from "../fieldgroup_vars_css.mjs";
import {useDOMRef as $c234463e9ef56637$export$c2c55ef9111cafd8} from "../utils/useDOMRef.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useRadioGroup as $jmxtg$useRadioGroup} from "react-aria/useRadioGroup";
import $jmxtg$react from "react";
import {useRadioGroupState as $jmxtg$useRadioGroupState} from "react-stately/useRadioGroupState";


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









const $a592e2b53348b1bb$export$a98f0dcb43a68a25 = /*#__PURE__*/ (0, $jmxtg$react).forwardRef(function RadioGroup(props, ref) {
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let { isEmphasized: isEmphasized, children: children, orientation: orientation = 'vertical' } = props;
    let domRef = (0, $c234463e9ef56637$export$c2c55ef9111cafd8)(ref);
    let state = (0, $jmxtg$useRadioGroupState)(props);
    let { radioGroupProps: radioGroupProps, ...otherProps } = (0, $jmxtg$useRadioGroup)(props, state);
    return /*#__PURE__*/ (0, $jmxtg$react).createElement((0, $3967792f95357356$export$a455218a85c89869), {
        ...props,
        ...otherProps,
        ref: domRef,
        wrapperClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jmxtg$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup'),
        elementType: "span"
    }, /*#__PURE__*/ (0, $jmxtg$react).createElement("div", {
        ...radioGroupProps,
        className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($jmxtg$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup-group', {
            'spectrum-FieldGroup-group--horizontal': orientation === 'horizontal'
        })
    }, /*#__PURE__*/ (0, $jmxtg$react).createElement((0, $d94927a7c7b6e45d$export$b118023277d4a5c3).Provider, {
        value: {
            isEmphasized: isEmphasized,
            state: state
        }
    }, children)));
});


export {$a592e2b53348b1bb$export$a98f0dcb43a68a25 as RadioGroup};
//# sourceMappingURL=RadioGroup.js.map
