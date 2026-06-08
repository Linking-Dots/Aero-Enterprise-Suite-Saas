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
exports.A4uCropRotate = A4uCropRotate;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uCropRotate(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M23,21h3V10.5a.5.5,0,0,0-.5-.5H16v3h7Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M28.5,23H13V6.5a.5.5,0,0,0-.5-.5h-2a.5.5,0,0,0-.5.5V10H6.5a.5.5,0,0,0-.5.5v2a.5.5,0,0,0,.5.5H10V25.5a.5.5,0,0,0,.5.5H23v3.5a.5.5,0,0,0,.5.5h2a.5.5,0,0,0,.5-.5V26h2.5a.5.5,0,0,0,.5-.5v-2A.5.5,0,0,0,28.5,23Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M28.264,3l-.23,0V.5a.5.5,0,0,0-.5-.5.493.493,0,0,0-.35.147L23.147,3.684a.5.5,0,0,0,0,.632l4.034,3.537a.493.493,0,0,0,.35.147.5.5,0,0,0,.5-.5V4.958l.23,0a3.786,3.786,0,0,1,3.781,3.892v.827a.325.325,0,0,0,.326.326h1.3A.326.326,0,0,0,34,9.674V8.847A5.74,5.74,0,0,0,28.264,3Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M8.819,28.147A.493.493,0,0,0,8.469,28a.5.5,0,0,0-.5.5v2.541l-.23,0a3.786,3.786,0,0,1-3.781-3.892v-.827A.325.325,0,0,0,3.629,26h-1.3A.326.326,0,0,0,2,26.326v.827A5.74,5.74,0,0,0,7.736,33l.23,0v2.5a.5.5,0,0,0,.5.5.493.493,0,0,0,.35-.147l4.034-3.537a.5.5,0,0,0,0-.632Z"
  }));
}