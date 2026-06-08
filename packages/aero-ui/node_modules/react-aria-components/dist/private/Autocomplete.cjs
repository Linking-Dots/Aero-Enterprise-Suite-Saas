var $048d76b84370f141$exports = require("./utils.cjs");
var $g5Gzq$reactariauseAutocomplete = require("react-aria/useAutocomplete");
var $g5Gzq$reactstatelyprivateautocompleteuseAutocompleteState = require("react-stately/private/autocomplete/useAutocompleteState");
var $g5Gzq$reactariamergeProps = require("react-aria/mergeProps");
var $g5Gzq$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "AutocompleteContext", function () { return $433949643203e332$export$36e687af51cd0967; });
$parcel$export(module.exports, "AutocompleteStateContext", function () { return $433949643203e332$export$68ee3368b6d68148; });
$parcel$export(module.exports, "SelectableCollectionContext", function () { return $433949643203e332$export$b0d3ecf7112093a7; });
$parcel$export(module.exports, "FieldInputContext", function () { return $433949643203e332$export$698f465ec27e93df; });
$parcel$export(module.exports, "Autocomplete", function () { return $433949643203e332$export$2f2b9559550c7bbc; });
/*
 * Copyright 2024 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 




const $433949643203e332$export$36e687af51cd0967 = /*#__PURE__*/ (0, $g5Gzq$react.createContext)(null);
const $433949643203e332$export$68ee3368b6d68148 = /*#__PURE__*/ (0, $g5Gzq$react.createContext)(null);
const $433949643203e332$export$b0d3ecf7112093a7 = /*#__PURE__*/ (0, $g5Gzq$react.createContext)(null);
const $433949643203e332$export$698f465ec27e93df = /*#__PURE__*/ (0, $g5Gzq$react.createContext)(null);
function $433949643203e332$export$2f2b9559550c7bbc(props) {
    let ctx = (0, $048d76b84370f141$exports.useSlottedContext)($433949643203e332$export$36e687af51cd0967, props.slot);
    props = (0, $g5Gzq$reactariamergeProps.mergeProps)(ctx, props);
    let { filter: filter, disableAutoFocusFirst: disableAutoFocusFirst } = props;
    let state = (0, $g5Gzq$reactstatelyprivateautocompleteuseAutocompleteState.useAutocompleteState)(props);
    let inputRef = (0, $g5Gzq$react.useRef)(null);
    let collectionRef = (0, $g5Gzq$react.useRef)(null);
    let { inputProps: inputProps, collectionProps: collectionProps, collectionRef: mergedCollectionRef, filter: filterFn } = (0, $g5Gzq$reactariauseAutocomplete.useAutocomplete)({
        ...(0, $048d76b84370f141$exports.removeDataAttributes)(props),
        filter: filter,
        disableAutoFocusFirst: disableAutoFocusFirst,
        inputRef: inputRef,
        collectionRef: collectionRef
    }, state);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($g5Gzq$react))).createElement((0, $048d76b84370f141$exports.Provider), {
        values: [
            [
                $433949643203e332$export$68ee3368b6d68148,
                state
            ],
            [
                $433949643203e332$export$698f465ec27e93df,
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                $433949643203e332$export$b0d3ecf7112093a7,
                {
                    ...collectionProps,
                    filter: filterFn,
                    ref: mergedCollectionRef
                }
            ]
        ]
    }, props.children);
}


//# sourceMappingURL=Autocomplete.cjs.map
