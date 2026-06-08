var $69fd630bd812ba47$exports = require("../utils/classNames.cjs");
var $0fc8553a4214494f$exports = require("../button/ClearButton.cjs");
require("../search_vars.css");
var $d2d4ce3e4a6482f9$exports = require("../search_vars_css.cjs");
var $827dbb466e199966$exports = require("../textfield/TextFieldBase.cjs");
var $1af2ca8553741739$exports = require("../form/Form.cjs");
var $544fc82701fc93e9$exports = require("../provider/Provider.cjs");
var $feede71cddc0c5f3$exports = require("../utils/Slots.cjs");
var $h4zqU$reactariauseSearchField = require("react-aria/useSearchField");
var $h4zqU$spectrumiconsuiMagnifier = require("@spectrum-icons/ui/Magnifier");
var $h4zqU$react = require("react");
var $h4zqU$reactstatelyuseSearchFieldState = require("react-stately/useSearchFieldState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "SearchField", function () { return $e327b654b915e170$export$b94867ecbd698f21; });
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










const $e327b654b915e170$export$b94867ecbd698f21 = /*#__PURE__*/ (0, $h4zqU$react.forwardRef)(function SearchField(props, ref) {
    props = (0, $feede71cddc0c5f3$exports.useSlotProps)(props, 'searchfield');
    props = (0, $544fc82701fc93e9$exports.useProviderProps)(props);
    props = (0, $1af2ca8553741739$exports.useFormProps)(props);
    let defaultIcon = /*#__PURE__*/ (0, ($parcel$interopDefault($h4zqU$react))).createElement((0, ($parcel$interopDefault($h4zqU$spectrumiconsuiMagnifier))), {
        "data-testid": "searchicon"
    });
    let { icon: icon = defaultIcon, isDisabled: isDisabled, UNSAFE_className: UNSAFE_className, placeholder: placeholder, ...otherProps } = props;
    let hasWarned = (0, $h4zqU$react.useRef)(false);
    (0, $h4zqU$react.useEffect)(()=>{
        if (placeholder && !hasWarned.current && process.env.NODE_ENV !== 'production') {
            console.warn('Placeholders are deprecated due to accessibility issues. Please use help text instead. See the docs for details: https://react-spectrum.adobe.com/react-spectrum/SearchField.html#help-text');
            hasWarned.current = true;
        }
    }, [
        placeholder
    ]);
    let state = (0, $h4zqU$reactstatelyuseSearchFieldState.useSearchFieldState)(props);
    let inputRef = (0, $h4zqU$react.useRef)(null);
    let { clearButtonProps: clearButtonProps, ...result } = (0, $h4zqU$reactariauseSearchField.useSearchField)(props, state, inputRef);
    let clearButton = /*#__PURE__*/ (0, ($parcel$interopDefault($h4zqU$react))).createElement((0, $0fc8553a4214494f$exports.ClearButton), {
        ...clearButtonProps,
        preventFocus: true,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d2d4ce3e4a6482f9$exports))), 'spectrum-ClearButton'),
        isDisabled: isDisabled
    });
    let validationState = props.validationState || (result.isInvalid ? 'invalid' : undefined);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($h4zqU$react))).createElement((0, $827dbb466e199966$exports.TextFieldBase), {
        ...otherProps,
        ...result,
        validationState: validationState,
        UNSAFE_className: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d2d4ce3e4a6482f9$exports))), 'spectrum-Search', 'spectrum-Textfield', {
            'is-disabled': isDisabled,
            'is-quiet': props.isQuiet,
            'spectrum-Search--invalid': validationState === 'invalid' && !isDisabled,
            'spectrum-Search--valid': validationState === 'valid' && !isDisabled
        }, UNSAFE_className),
        inputClassName: (0, $69fd630bd812ba47$exports.classNames)((0, ($parcel$interopDefault($d2d4ce3e4a6482f9$exports))), 'spectrum-Search-input'),
        ref: ref,
        inputRef: inputRef,
        isDisabled: isDisabled,
        icon: icon,
        wrapperChildren: state.value !== '' && !props.isReadOnly ? clearButton : undefined
    });
});


//# sourceMappingURL=SearchField.cjs.map
