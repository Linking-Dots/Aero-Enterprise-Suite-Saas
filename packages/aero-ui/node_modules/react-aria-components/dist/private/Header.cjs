var $048d76b84370f141$exports = require("./utils.cjs");
var $23oIV$reactariaCollectionBuilder = require("react-aria/CollectionBuilder");
var $23oIV$reactariaprivatecollectionsBaseCollection = require("react-aria/private/collections/BaseCollection");
var $23oIV$react = require("react");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "HeaderContext", function () { return $749e1f015a6d7f1a$export$e0e4026c12a8bdbb; });
$parcel$export(module.exports, "Header", function () { return $749e1f015a6d7f1a$export$8b251419efc915eb; });
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



const $749e1f015a6d7f1a$export$e0e4026c12a8bdbb = /*#__PURE__*/ (0, $23oIV$react.createContext)({});
const $749e1f015a6d7f1a$export$8b251419efc915eb = /*#__PURE__*/ (0, $23oIV$reactariaCollectionBuilder.createLeafComponent)((0, $23oIV$reactariaprivatecollectionsBaseCollection.HeaderNode), function Header(props, ref) {
    [props, ref] = (0, $048d76b84370f141$exports.useContextProps)(props, ref, $749e1f015a6d7f1a$export$e0e4026c12a8bdbb);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($23oIV$react))).createElement((0, $048d76b84370f141$exports.dom).header, {
        className: "react-aria-Header",
        ...props,
        ref: ref
    }, props.children);
});


//# sourceMappingURL=Header.cjs.map
