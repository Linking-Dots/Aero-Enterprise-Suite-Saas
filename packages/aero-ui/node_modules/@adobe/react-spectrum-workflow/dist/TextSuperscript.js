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
exports.A4uTextSuperscript = A4uTextSuperscript;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uTextSuperscript(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M3,4A1,1,0,0,0,2,5v6a1,1,0,0,0,1,1H5a1,1,0,0,0,1-1V8h6V28H9a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1H19a1,1,0,0,0,1-1V29a1,1,0,0,0-1-1H16V8h6v3a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V5a1,1,0,0,0-1-1Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M31.742,12c-.121,0-.16-.039-.16-.141V3.805a8.128,8.128,0,0,1-2.1.72c-.119.02-.158,0-.158-.121v-1.7c0-.1.02-.141.119-.16a9.969,9.969,0,0,0,2.78-1.2.505.505,0,0,1,.3-.08H33.9c.08,0,.1.039.1.138V11.859c0,.1-.039.141-.119.141Z"
  }));
}