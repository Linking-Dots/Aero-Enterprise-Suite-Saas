import {classNames as $512ba93e663f149c$export$ce4ab0c55987d1ff} from "../utils/classNames.js";
import {ClearButton as $cf8b586db4c34baa$export$13ec83e50bf04290} from "../button/ClearButton.js";
import "../search_vars.css";
import $dI9FE$search_vars_cssmjs from "../search_vars_css.mjs";
import {TextFieldBase as $1f88830e88ee8f61$export$d22444a338b6e3c2} from "../textfield/TextFieldBase.js";
import {useFormProps as $d23ca6800ac02cf1$export$a6b5be5c6b451665} from "../form/Form.js";
import {useProviderProps as $089943c7a219141c$export$521c373ccc32c300} from "../provider/Provider.js";
import {useSlotProps as $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3} from "../utils/Slots.js";
import {useSearchField as $dI9FE$useSearchField} from "react-aria/useSearchField";
import $dI9FE$spectrumiconsuiMagnifier from "@spectrum-icons/ui/Magnifier";
import $dI9FE$react, {forwardRef as $dI9FE$forwardRef, useRef as $dI9FE$useRef, useEffect as $dI9FE$useEffect} from "react";
import {useSearchFieldState as $dI9FE$useSearchFieldState} from "react-stately/useSearchFieldState";


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










const $f73cad999d6c6ed8$export$b94867ecbd698f21 = /*#__PURE__*/ (0, $dI9FE$forwardRef)(function SearchField(props, ref) {
    props = (0, $68f4bc2c1abc5618$export$1e5c9e6e4e15efe3)(props, 'searchfield');
    props = (0, $089943c7a219141c$export$521c373ccc32c300)(props);
    props = (0, $d23ca6800ac02cf1$export$a6b5be5c6b451665)(props);
    let defaultIcon = /*#__PURE__*/ (0, $dI9FE$react).createElement((0, $dI9FE$spectrumiconsuiMagnifier), {
        "data-testid": "searchicon"
    });
    let { icon: icon = defaultIcon, isDisabled: isDisabled, UNSAFE_className: UNSAFE_className, placeholder: placeholder, ...otherProps } = props;
    let hasWarned = (0, $dI9FE$useRef)(false);
    (0, $dI9FE$useEffect)(()=>{
        if (placeholder && !hasWarned.current && process.env.NODE_ENV !== 'production') {
            console.warn('Placeholders are deprecated due to accessibility issues. Please use help text instead. See the docs for details: https://react-spectrum.adobe.com/react-spectrum/SearchField.html#help-text');
            hasWarned.current = true;
        }
    }, [
        placeholder
    ]);
    let state = (0, $dI9FE$useSearchFieldState)(props);
    let inputRef = (0, $dI9FE$useRef)(null);
    let { clearButtonProps: clearButtonProps, ...result } = (0, $dI9FE$useSearchField)(props, state, inputRef);
    let clearButton = /*#__PURE__*/ (0, $dI9FE$react).createElement((0, $cf8b586db4c34baa$export$13ec83e50bf04290), {
        ...clearButtonProps,
        preventFocus: true,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($dI9FE$search_vars_cssmjs))), 'spectrum-ClearButton'),
        isDisabled: isDisabled
    });
    let validationState = props.validationState || (result.isInvalid ? 'invalid' : undefined);
    return /*#__PURE__*/ (0, $dI9FE$react).createElement((0, $1f88830e88ee8f61$export$d22444a338b6e3c2), {
        ...otherProps,
        ...result,
        validationState: validationState,
        UNSAFE_className: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($dI9FE$search_vars_cssmjs))), 'spectrum-Search', 'spectrum-Textfield', {
            'is-disabled': isDisabled,
            'is-quiet': props.isQuiet,
            'spectrum-Search--invalid': validationState === 'invalid' && !isDisabled,
            'spectrum-Search--valid': validationState === 'valid' && !isDisabled
        }, UNSAFE_className),
        inputClassName: (0, $512ba93e663f149c$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($dI9FE$search_vars_cssmjs))), 'spectrum-Search-input'),
        ref: ref,
        inputRef: inputRef,
        isDisabled: isDisabled,
        icon: icon,
        wrapperChildren: state.value !== '' && !props.isReadOnly ? clearButton : undefined
    });
});


export {$f73cad999d6c6ed8$export$b94867ecbd698f21 as SearchField};
//# sourceMappingURL=SearchField.js.map
