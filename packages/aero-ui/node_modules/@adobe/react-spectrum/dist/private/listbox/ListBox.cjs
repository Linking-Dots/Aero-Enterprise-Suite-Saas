var $cb7ee1d9d5613db9$exports = require("./ListBoxBase.cjs");
var $65aea7b37663976b$exports = require("../utils/useDOMRef.cjs");
var $1K4oU$react = require("react");
var $1K4oU$reactstatelyuseListState = require("react-stately/useListState");


function $parcel$interopDefault(a) {
  return a && a.__esModule ? a.default : a;
}

function $parcel$export(e, n, v, s) {
  Object.defineProperty(e, n, {get: v, set: s, enumerable: true, configurable: true});
}

$parcel$export(module.exports, "ListBox", function () { return $8fa54805d1ab03f4$export$41f133550aa26f48; });
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



const $8fa54805d1ab03f4$export$41f133550aa26f48 = /*#__PURE__*/ (0, ($parcel$interopDefault($1K4oU$react))).forwardRef(function ListBox(props, ref) {
    let state = (0, $1K4oU$reactstatelyuseListState.useListState)(props);
    let layout = (0, $cb7ee1d9d5613db9$exports.useListBoxLayout)();
    let domRef = (0, $65aea7b37663976b$exports.useDOMRef)(ref);
    return /*#__PURE__*/ (0, ($parcel$interopDefault($1K4oU$react))).createElement((0, $cb7ee1d9d5613db9$exports.ListBoxBase), {
        ...props,
        ref: domRef,
        state: state,
        layout: layout
    });
});


//# sourceMappingURL=ListBox.cjs.map
