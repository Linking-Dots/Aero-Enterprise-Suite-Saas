import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {Field as $adcd096854d27620$export$a455218a85c89869} from "../label/Field.mjs";
import {RadioContext as $561e3d9e91a7fb1c$export$b118023277d4a5c3} from "./context.mjs";
import "../fieldgroup_vars.css";
import $7EwlE$fieldgroup_vars_cssmjs from "../fieldgroup_vars_css.mjs";
import {useDOMRef as $3c2c983d5210446c$export$c2c55ef9111cafd8} from "../utils/useDOMRef.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useRadioGroup as $7EwlE$useRadioGroup} from "react-aria/useRadioGroup";
import $7EwlE$react from "react";
import {useRadioGroupState as $7EwlE$useRadioGroupState} from "react-stately/useRadioGroupState";


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









const $f011f1cdcd1c9966$export$a98f0dcb43a68a25 = /*#__PURE__*/ (0, $7EwlE$react).forwardRef(function RadioGroup(props, ref) {
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let { isEmphasized: isEmphasized, children: children, orientation: orientation = 'vertical' } = props;
    let domRef = (0, $3c2c983d5210446c$export$c2c55ef9111cafd8)(ref);
    let state = (0, $7EwlE$useRadioGroupState)(props);
    let { radioGroupProps: radioGroupProps, ...otherProps } = (0, $7EwlE$useRadioGroup)(props, state);
    return /*#__PURE__*/ (0, $7EwlE$react).createElement((0, $adcd096854d27620$export$a455218a85c89869), {
        ...props,
        ...otherProps,
        ref: domRef,
        wrapperClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7EwlE$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup'),
        elementType: "span"
    }, /*#__PURE__*/ (0, $7EwlE$react).createElement("div", {
        ...radioGroupProps,
        className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($7EwlE$fieldgroup_vars_cssmjs))), 'spectrum-FieldGroup-group', {
            'spectrum-FieldGroup-group--horizontal': orientation === 'horizontal'
        })
    }, /*#__PURE__*/ (0, $7EwlE$react).createElement((0, $561e3d9e91a7fb1c$export$b118023277d4a5c3).Provider, {
        value: {
            isEmphasized: isEmphasized,
            state: state
        }
    }, children)));
});


export {$f011f1cdcd1c9966$export$a98f0dcb43a68a25 as RadioGroup};
//# sourceMappingURL=RadioGroup.mjs.map
