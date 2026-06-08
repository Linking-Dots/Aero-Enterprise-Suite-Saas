var $048d76b84370f141$exports = require("./utils.cjs");
var $9a60bd90621ebc78$exports = require("./SharedElementTransition.cjs");
var $hJTaW$reactariauseToggleButtonGroup = require("react-aria/useToggleButtonGroup");
var $hJTaW$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $hJTaW$reactariamergeProps = require("react-aria/mergeProps");
var $hJTaW$react = require("react");
var $hJTaW$reactstatelyuseToggleGroupState = require("react-stately/useToggleGroupState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ToggleButtonGroupContext", function () { return $1a5ce205589a38e1$export$298258635ae0dd97; });
$parcel$export(module.exports, "ToggleGroupStateContext", function () { return $1a5ce205589a38e1$export$a8a71863db173133; });
$parcel$export(module.exports, "ToggleButtonGroup", function () { return $1a5ce205589a38e1$export$40258cc1d95ff477; });
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






const $1a5ce205589a38e1$export$298258635ae0dd97 = /*#__PURE__*/ (0, $hJTaW$react.createContext)({});
const $1a5ce205589a38e1$export$a8a71863db173133 = /*#__PURE__*/ (0, $hJTaW$react.createContext)(null);
const $1a5ce205589a38e1$export$40258cc1d95ff477 = /*#__PURE__*/ (0, $hJTaW$react.forwardRef)(function ToggleButtonGroup(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $1a5ce205589a38e1$export$298258635ae0dd97);
    let state = (0, $hJTaW$reactstatelyuseToggleGroupState.useToggleGroupState)(props);
    let { groupProps: groupProps } = (0, $hJTaW$reactariauseToggleButtonGroup.useToggleButtonGroup)(props, state, ref);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            orientation: props.orientation || 'horizontal',
            isDisabled: state.isDisabled,
            state: state
        },
        defaultClassName: 'react-aria-ToggleButtonGroup'
    });
    let DOMProps = (0, $hJTaW$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    return /*#__PURE__*/ (0, ($parcel$interopDefault($hJTaW$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $hJTaW$reactariamergeProps.mergeProps)(DOMProps, renderProps, groupProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'horizontal',
        "data-disabled": props.isDisabled || undefined
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hJTaW$react))).createElement($1a5ce205589a38e1$export$a8a71863db173133.Provider, {
        value: state
    }, /*#__PURE__*/ (0, ($parcel$interopDefault($hJTaW$react))).createElement((0, $9a60bd90621ebc78$exports.SharedElementTransition), null, renderProps.children)));
});


//# sourceMappingURL=ToggleButtonGroup.cjs.map
