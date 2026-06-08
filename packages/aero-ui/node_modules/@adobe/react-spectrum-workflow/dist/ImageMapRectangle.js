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
exports.A4uImageMapRectangle = A4uImageMapRectangle;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uImageMapRectangle(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.5,10a.5.5,0,0,0,.5-.5v-7a.5.5,0,0,0-.5-.5h-7a.5.5,0,0,0-.5.5V4H10V2.5A.5.5,0,0,0,9.5,2h-7a.5.5,0,0,0-.5.5v7a.5.5,0,0,0,.5.5H4V26H2.5a.5.5,0,0,0-.5.5v7a.5.5,0,0,0,.5.5h7a.5.5,0,0,0,.5-.5V32H26v1.5a.5.5,0,0,0,.5.5h7a.5.5,0,0,0,.5-.5v-7a.5.5,0,0,0-.5-.5H32V10ZM4,4H8V8H4ZM8,32H4V28H8Zm18-5.5V30H10V26.5a.5.5,0,0,0-.5-.5H6V10H9.5a.5.5,0,0,0,.5-.5V6H26V9.5a.5.5,0,0,0,.5.5H30V26H26.5A.5.5,0,0,0,26,26.5ZM32,32H28V28h4ZM28,8V4h4V8Z"
  }));
}