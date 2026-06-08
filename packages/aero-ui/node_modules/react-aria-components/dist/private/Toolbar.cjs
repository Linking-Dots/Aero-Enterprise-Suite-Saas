var $048d76b84370f141$exports = require("./utils.cjs");
var $fprd7$reactariauseToolbar = require("react-aria/useToolbar");
var $fprd7$reactariafilterDOMProps = require("react-aria/filterDOMProps");
var $fprd7$reactariamergeProps = require("react-aria/mergeProps");
var $fprd7$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ToolbarContext", function () { return $bbbd769e06a66df4$export$6311e7ab80ef752f; });
$parcel$export(module.exports, "Toolbar", function () { return $bbbd769e06a66df4$export$4c260019440d418f; });
/*
 * Copyright 2023 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 




const $bbbd769e06a66df4$export$6311e7ab80ef752f = /*#__PURE__*/ (0, $fprd7$react.createContext)({});
const $bbbd769e06a66df4$export$4c260019440d418f = /*#__PURE__*/ (0, $fprd7$react.forwardRef)(function Toolbar(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $bbbd769e06a66df4$export$6311e7ab80ef752f);
    let { toolbarProps: toolbarProps } = (0, $fprd7$reactariauseToolbar.useToolbar)(props, ref);
    let renderProps = (0, $048d76b84370f141$exports.useRenderProps)({
        ...props,
        values: {
            orientation: props.orientation || 'horizontal'
        },
        defaultClassName: 'react-aria-Toolbar'
    });
    let DOMProps = (0, $fprd7$reactariafilterDOMProps.filterDOMProps)(props, {
        global: true
    });
    delete DOMProps.id;
    return /*#__PURE__*/ (0, ($parcel$interopDefault($fprd7$react))).createElement((0, $048d76b84370f141$exports.dom).div, {
        ...(0, $fprd7$reactariamergeProps.mergeProps)(DOMProps, renderProps, toolbarProps),
        ref: ref,
        slot: props.slot || undefined,
        "data-orientation": props.orientation || 'horizontal'
    }, renderProps.children);
});


//# sourceMappingURL=Toolbar.cjs.map
