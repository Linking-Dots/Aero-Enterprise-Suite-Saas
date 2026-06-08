import {classNames as $6e6392558d48dfec$export$ce4ab0c55987d1ff} from "../utils/classNames.mjs";
import {ClearButton as $ab14010a528467be$export$13ec83e50bf04290} from "../button/ClearButton.mjs";
import "../search_vars.css";
import $lBdcA$search_vars_cssmjs from "../search_vars_css.mjs";
import {TextFieldBase as $b312f2102feb9487$export$d22444a338b6e3c2} from "../textfield/TextFieldBase.mjs";
import {useFormProps as $c29c48d4ef19ffc4$export$a6b5be5c6b451665} from "../form/Form.mjs";
import {useProviderProps as $71dfb0e0358a12de$export$521c373ccc32c300} from "../provider/Provider.mjs";
import {useSlotProps as $62024859ff9f1f8a$export$1e5c9e6e4e15efe3} from "../utils/Slots.mjs";
import {useSearchField as $lBdcA$useSearchField} from "react-aria/useSearchField";
import $lBdcA$spectrumiconsuiMagnifier from "@spectrum-icons/ui/Magnifier";
import $lBdcA$react, {forwardRef as $lBdcA$forwardRef, useRef as $lBdcA$useRef, useEffect as $lBdcA$useEffect} from "react";
import {useSearchFieldState as $lBdcA$useSearchFieldState} from "react-stately/useSearchFieldState";


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










const $f30c22bfcced4638$export$b94867ecbd698f21 = /*#__PURE__*/ (0, $lBdcA$forwardRef)(function SearchField(props, ref) {
    props = (0, $62024859ff9f1f8a$export$1e5c9e6e4e15efe3)(props, 'searchfield');
    props = (0, $71dfb0e0358a12de$export$521c373ccc32c300)(props);
    props = (0, $c29c48d4ef19ffc4$export$a6b5be5c6b451665)(props);
    let defaultIcon = /*#__PURE__*/ (0, $lBdcA$react).createElement((0, $lBdcA$spectrumiconsuiMagnifier), {
        "data-testid": "searchicon"
    });
    let { icon: icon = defaultIcon, isDisabled: isDisabled, UNSAFE_className: UNSAFE_className, placeholder: placeholder, ...otherProps } = props;
    let hasWarned = (0, $lBdcA$useRef)(false);
    (0, $lBdcA$useEffect)(()=>{
        if (placeholder && !hasWarned.current && process.env.NODE_ENV !== 'production') {
            console.warn('Placeholders are deprecated due to accessibility issues. Please use help text instead. See the docs for details: https://react-spectrum.adobe.com/react-spectrum/SearchField.html#help-text');
            hasWarned.current = true;
        }
    }, [
        placeholder
    ]);
    let state = (0, $lBdcA$useSearchFieldState)(props);
    let inputRef = (0, $lBdcA$useRef)(null);
    let { clearButtonProps: clearButtonProps, ...result } = (0, $lBdcA$useSearchField)(props, state, inputRef);
    let clearButton = /*#__PURE__*/ (0, $lBdcA$react).createElement((0, $ab14010a528467be$export$13ec83e50bf04290), {
        ...clearButtonProps,
        preventFocus: true,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lBdcA$search_vars_cssmjs))), 'spectrum-ClearButton'),
        isDisabled: isDisabled
    });
    let validationState = props.validationState || (result.isInvalid ? 'invalid' : undefined);
    return /*#__PURE__*/ (0, $lBdcA$react).createElement((0, $b312f2102feb9487$export$d22444a338b6e3c2), {
        ...otherProps,
        ...result,
        validationState: validationState,
        UNSAFE_className: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lBdcA$search_vars_cssmjs))), 'spectrum-Search', 'spectrum-Textfield', {
            'is-disabled': isDisabled,
            'is-quiet': props.isQuiet,
            'spectrum-Search--invalid': validationState === 'invalid' && !isDisabled,
            'spectrum-Search--valid': validationState === 'valid' && !isDisabled
        }, UNSAFE_className),
        inputClassName: (0, $6e6392558d48dfec$export$ce4ab0c55987d1ff)((0, ($parcel$interopDefault($lBdcA$search_vars_cssmjs))), 'spectrum-Search-input'),
        ref: ref,
        inputRef: inputRef,
        isDisabled: isDisabled,
        icon: icon,
        wrapperChildren: state.value !== '' && !props.isReadOnly ? clearButton : undefined
    });
});


export {$f30c22bfcced4638$export$b94867ecbd698f21 as SearchField};
//# sourceMappingURL=SearchField.mjs.map
