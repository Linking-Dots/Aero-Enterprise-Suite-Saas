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
exports.A4uShop = A4uShop;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uShop(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M34.93944,16H1.06054a.8.8,0,0,1-.76916-1.01978l3.50134-12.255A1,1,0,0,1,4.75428,2H31.24572a1,1,0,0,1,.96156.72522l3.50134,12.255A.8.8,0,0,1,34.93944,16ZM30,18v6H14V18H12V32H6V18H4V32a2,2,0,0,0,2,2H30a2,2,0,0,0,2-2V18ZM4,14H6L8,4H6Zm8.5,0h2l1-10h-2Zm8-10,1,10h2l-1-10ZM30,4H28l2,10h2Z"
  }));
}