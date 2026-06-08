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
exports.A4uMonitoring = A4uMonitoring;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uMonitoring(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35,2H1A1,1,0,0,0,0,3V25a1,1,0,0,0,1,1H14v3a1,1,0,0,1-1,1H11a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1H25a1,1,0,0,0,1-1V31a1,1,0,0,0-1-1H23a1,1,0,0,1-1-1V26H35a1,1,0,0,0,1-1V3A1,1,0,0,0,35,2ZM32,17.883H24.222a1.378,1.378,0,0,1-1.237-.83l-2.3-5-4.249,8.072a1.368,1.368,0,0,1-1.2.757H15.2a1.383,1.383,0,0,1-1.2-.83l-1.845-4L11.09,17.369a1.337,1.337,0,0,1-1.041.514H4V14H9l2.428-3.609a1.346,1.346,0,0,1,1.217-.5,1.4,1.4,0,0,1,1.061.818l1.61,3.5,4.249-8.072A1.405,1.405,0,0,1,20.8,5.376a1.378,1.378,0,0,1,1.2.829L25.5,14H32Z"
  }));
}