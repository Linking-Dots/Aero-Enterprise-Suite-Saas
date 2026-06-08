/**
Copyright 2024 Adobe. All rights reserved.
This file is licensed to you under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License. You may obtain a copy
of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under
the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
OF ANY KIND, either express or implied. See the License for the specific language
governing permissions and limitations under the License.
**/
"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.A4uImageSearch = A4uImageSearch;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uImageSearch(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.63391,33.86609l-5.16833-5.16834a8.02029,8.02029,0,1,0-1.76777,1.76777l5.16828,5.16833a1.25,1.25,0,0,0,1.76782-1.76776ZM18,24a6,6,0,1,1,6,6A6,6,0,0,1,18,24Zm-1.22656-6.88257-5.50122-5.50122A2,2,0,0,0,8.444,11.616L2,18.058V4H30V16.04529a10.00983,10.00983,0,0,1,2,2.00964V3a1,1,0,0,0-1-1H1A1,1,0,0,0,0,3V25a1,1,0,0,0,1,1H14.20154A9.94618,9.94618,0,0,1,16.77344,17.11743ZM22,10.05078a2.7,2.7,0,1,0,2.70005-2.69995H24.7A2.7,2.7,0,0,0,22,10.05078Z"
  }));
}