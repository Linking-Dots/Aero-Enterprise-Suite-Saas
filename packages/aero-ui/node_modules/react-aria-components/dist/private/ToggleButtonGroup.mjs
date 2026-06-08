import {dom as $7230ffa83bc0c2cf$export$df3a06d6289f983e, useContextProps as $7230ffa83bc0c2cf$export$29f1550f4b0d4415, useRenderProps as $7230ffa83bc0c2cf$export$4d86445c2cf5e3} from "./utils.mjs";
import {SharedElementTransition as $792f28e438b9ad5f$export$758399f318e6385a} from "./SharedElementTransition.mjs";
import {useToggleButtonGroup as $9mIod$useToggleButtonGroup} from "react-aria/useToggleButtonGroup";
import {filterDOMProps as $9mIod$filterDOMProps} from "react-aria/filterDOMProps";
import {mergeProps as $9mIod$mergeProps} from "react-aria/mergeProps";
import $9mIod$react, {createContext as $9mIod$createContext, forwardRef as $9mIod$forwardRef} from "react";
import {useToggleGroupState as $9mIod$useToggleGroupState} from "react-stately/useToggleGroupState";

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






const $bc25b811ec97a172$export$298258635ae0dd97 = /*#__PURE__*/ (0, $9mIod$createContext)({});
const $bc25b811ec97a172$export$a8a71863db173133 = /*#__PURE__*/ (0, $9mIod$createContext)(null);
const $bc25b811ec97a172$export$40258cc1d95ff477 = /*#__PURE__*/ (0, $9mIod$forwardRef)(function ToggleButtonGroup(props, ref) {
    [props, ref] = (0, $7230ffa83bc0c2cf$export$29f1550f4b0d4415)(props, ref, $bc25b811ec97a172$export$298258635ae0dd97);
    let state = (0, $9mIod$useToggleGroupState)(props);
    let { groupProps: groupProps } = (0, $9mIod$useToggleButtonGroup)(props, state, ref);
    let renderProps = (0, $7230ffa83bc0c2cf$export$4d86445c2cf5e3)({
        ...props,
        values: {
            orientation: props.orientation || 'horizontal',
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ToggleButtonGroup'
    });
    let DOMProps = (0, $9mIod$filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, $9mIod$react).createElement((0, $7230ffa83bc0c2cf$export$df3a06d6289f983e).div, {
        ...(0, $9mIod$mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'horizontal',
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, $9mIod$react).createElement($bc25b811ec97a172$export$a8a71863db173133.Provider, {
        value: state
    }, /*#__PURE__*/ (0, $9mIod$react).createElement((0, $792f28e438b9ad5f$export$758399f318e6385a), null, renderProps.children)));
});


export {$bc25b811ec97a172$export$298258635ae0dd97 as ToggleButtonGroupContext, $bc25b811ec97a172$export$a8a71863db173133 as ToggleGroupStateContext, $bc25b811ec97a172$export$40258cc1d95ff477 as ToggleButtonGroup};
//# sourceMappingURL=ToggleButtonGroup.mjs.map
