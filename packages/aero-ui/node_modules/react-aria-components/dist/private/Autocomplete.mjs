import {Provider as $7230ffa83bc0c2cf$export$2881499e37b75b9a, removeDataAttributes as $7230ffa83bc0c2cf$export$ef03459518577ad4, useSlottedContext as $7230ffa83bc0c2cf$export$fabf2dc03a41866e} from "./utils.mjs";
import {useAutocomplete as $1J3Gn$useAutocomplete} from "react-aria/useAutocomplete";
import {useAutocompleteState as $1J3Gn$useAutocompleteState} from "react-stately/private/autocomplete/useAutocompleteState";
import {mergeProps as $1J3Gn$mergeProps} from "react-aria/mergeProps";
import $1J3Gn$react, {createContext as $1J3Gn$createContext, useRef as $1J3Gn$useRef} from "react";

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




const $4b38b5b75ecc6208$export$36e687af51cd0967 = /*#__PURE__*/ (0, $1J3Gn$createContext)(null);
const $4b38b5b75ecc6208$export$68ee3368b6d68148 = /*#__PURE__*/ (0, $1J3Gn$createContext)(null);
const $4b38b5b75ecc6208$export$b0d3ecf7112093a7 = /*#__PURE__*/ (0, $1J3Gn$createContext)(null);
const $4b38b5b75ecc6208$export$698f465ec27e93df = /*#__PURE__*/ (0, $1J3Gn$createContext)(null);
function $4b38b5b75ecc6208$export$2f2b9559550c7bbc(props) {
    let ctx = (0, $7230ffa83bc0c2cf$export$fabf2dc03a41866e)($4b38b5b75ecc6208$export$36e687af51cd0967, props.slot);
    props = (0, $1J3Gn$mergeProps)(ctx, props);
    let { filter: filter, disableAutoFocusFirst: disableAutoFocusFirst } = props;
    let state = (0, $1J3Gn$useAutocompleteState)(props);
    let inputRef = (0, $1J3Gn$useRef)(null);
    let collectionRef = (0, $1J3Gn$useRef)(null);
    let { inputProps: inputProps, collectionProps: collectionProps, collectionRef: mergedCollectionRef, filter: filterFn } = (0, $1J3Gn$useAutocomplete)({
        ...(0, $7230ffa83bc0c2cf$export$ef03459518577ad4)(props),
        filter: filter,
        disableAutoFocusFirst: disableAutoFocusFirst,
        inputRef: inputRef,
        collectionRef: collectionRef
    }, state);
    return /*#__PURE__*/ (0, $1J3Gn$react).createElement((0, $7230ffa83bc0c2cf$export$2881499e37b75b9a), {
        values: [
            [
                $4b38b5b75ecc6208$export$68ee3368b6d68148,
                state
            ],
            [
                $4b38b5b75ecc6208$export$698f465ec27e93df,
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                $4b38b5b75ecc6208$export$b0d3ecf7112093a7,
                {
                    ...collectionProps,
                    filter: filterFn,
                    ref: mergedCollectionRef
                }
            ]
        ]
    }, props.children);
}


export {$4b38b5b75ecc6208$export$36e687af51cd0967 as AutocompleteContext, $4b38b5b75ecc6208$export$68ee3368b6d68148 as AutocompleteStateContext, $4b38b5b75ecc6208$export$b0d3ecf7112093a7 as SelectableCollectionContext, $4b38b5b75ecc6208$export$698f465ec27e93df as FieldInputContext, $4b38b5b75ecc6208$export$2f2b9559550c7bbc as Autocomplete};
//# sourceMappingURL=Autocomplete.mjs.map
