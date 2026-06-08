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
exports.A4uSampler = A4uSampler;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSampler(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M22.457,17.037,8.232,31.262a2.471,2.471,0,1,1-3.494-3.494L18.963,13.543ZM29.728,2.106a3.591,3.591,0,0,0-2.546,1.055L22.657,7.686,21.243,6.272a1,1,0,0,0-1.414,0L16.467,9.633a1,1,0,0,0,0,1.414l1.081,1.082L3.324,26.354a4.47,4.47,0,1,0,6.322,6.322L23.871,18.452l1.082,1.081a1,1,0,0,0,1.414,0l3.361-3.361a1,1,0,0,0,0-1.415l-1.414-1.414,4.525-4.525a3.6,3.6,0,0,0,0-5.092l-.565-.565a3.592,3.592,0,0,0-2.546-1.055Z"
  }));
}