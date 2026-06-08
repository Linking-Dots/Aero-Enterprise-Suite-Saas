import {Provider as $b7b7a92703138c9b$export$2881499e37b75b9a, removeDataAttributes as $b7b7a92703138c9b$export$ef03459518577ad4, useSlottedContext as $b7b7a92703138c9b$export$fabf2dc03a41866e} from "./utils.js";
import {useAutocomplete as $ahflH$useAutocomplete} from "react-aria/useAutocomplete";
import {useAutocompleteState as $ahflH$useAutocompleteState} from "react-stately/private/autocomplete/useAutocompleteState";
import {mergeProps as $ahflH$mergeProps} from "react-aria/mergeProps";
import $ahflH$react, {createContext as $ahflH$createContext, useRef as $ahflH$useRef} from "react";

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




const $8f09b710ef85b337$export$36e687af51cd0967 = /*#__PURE__*/ (0, $ahflH$createContext)(null);
const $8f09b710ef85b337$export$68ee3368b6d68148 = /*#__PURE__*/ (0, $ahflH$createContext)(null);
const $8f09b710ef85b337$export$b0d3ecf7112093a7 = /*#__PURE__*/ (0, $ahflH$createContext)(null);
const $8f09b710ef85b337$export$698f465ec27e93df = /*#__PURE__*/ (0, $ahflH$createContext)(null);
function $8f09b710ef85b337$export$2f2b9559550c7bbc(props) {
    let ctx = (0, $b7b7a92703138c9b$export$fabf2dc03a41866e)($8f09b710ef85b337$export$36e687af51cd0967, props.slot);
    props = (0, $ahflH$mergeProps)(ctx, props);
    let { filter: filter, disableAutoFocusFirst: disableAutoFocusFirst } = props;
    let state = (0, $ahflH$useAutocompleteState)(props);
    let inputRef = (0, $ahflH$useRef)(null);
    let collectionRef = (0, $ahflH$useRef)(null);
    let { inputProps: inputProps, collectionProps: collectionProps, collectionRef: mergedCollectionRef, filter: filterFn } = (0, $ahflH$useAutocomplete)({
        ...(0, $b7b7a92703138c9b$export$ef03459518577ad4)(props),
        filter: filter,
        disableAutoFocusFirst: disableAutoFocusFirst,
        inputRef: inputRef,
        collectionRef: collectionRef
    }, state);
    return /*#__PURE__*/ (0, $ahflH$react).createElement((0, $b7b7a92703138c9b$export$2881499e37b75b9a), {
        values: [
            [
                $8f09b710ef85b337$export$68ee3368b6d68148,
                state
            ],
            [
                $8f09b710ef85b337$export$698f465ec27e93df,
                {
                    ...inputProps,
                    ref: inputRef
                }
            ],
            [
                $8f09b710ef85b337$export$b0d3ecf7112093a7,
                {
                    ...collectionProps,
                    filter: filterFn,
                    ref: mergedCollectionRef
                }
            ]
        ]
    }, props.children);
}


export {$8f09b710ef85b337$export$36e687af51cd0967 as AutocompleteContext, $8f09b710ef85b337$export$68ee3368b6d68148 as AutocompleteStateContext, $8f09b710ef85b337$export$b0d3ecf7112093a7 as SelectableCollectionContext, $8f09b710ef85b337$export$698f465ec27e93df as FieldInputContext, $8f09b710ef85b337$export$2f2b9559550c7bbc as Autocomplete};
//# sourceMappingURL=Autocomplete.js.map
