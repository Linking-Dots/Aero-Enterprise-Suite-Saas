var $048d76b84370f141$exports = require("./utils.cjs");
var $NBgCv$reactariaprivatecollectionsHidden = require("react-aria/private/collections/Hidden");
var $NBgCv$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "LabelContext", function () { return $d5d46822336ca1e1$export$75b6ee27786ba447; });
$parcel$export(module.exports, "Label", function () { return $d5d46822336ca1e1$export$b04be29aa201d4f5; });
/*
 * Copyright 2022 Adobe. All rights reserved.
 * This file is licensed to you under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy
 * of the License at http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
 * OF ANY KIND, either express or implied. See the License for the specific language
 * governing permissions and limitations under the License.
 */ 


const $d5d46822336ca1e1$export$75b6ee27786ba447 = /*#__PURE__*/ (0, $NBgCv$react.createContext)({});
const $d5d46822336ca1e1$export$b04be29aa201d4f5 = /*#__PURE__*/ (0, $NBgCv$reactariaprivatecollectionsHidden.createHideableComponent)(function Label(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $d5d46822336ca1e1$export$75b6ee27786ba447);
    let { elementType: elementType = 'label', ...labelProps } = props;
    let ElementType = (0, $048d76b84370f141$exports.dom)[elementType];
    // @ts-ignore
    return /*#__PURE__*/ (0, ($parcel$interopDefault($NBgCv$react))).createElement(ElementType, {
        className: "react-aria-Label",
        ...labelProps,
        ref: ref
    });
});


//# sourceMappingURL=Label.cjs.map
