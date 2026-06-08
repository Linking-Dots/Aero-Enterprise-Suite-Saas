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
exports.A4uWatch = A4uWatch;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uWatch(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M8,6A1.914,1.914,0,0,0,6,8V28a2.02,2.02,0,0,0,2,2,2.112,2.112,0,0,1,2,2v3a1,1,0,0,0,1,1H25a1,1,0,0,0,1-1V32a2.112,2.112,0,0,1,2-2,2.021,2.021,0,0,0,2-2V16h1a1,1,0,0,0,1-1V13a1,1,0,0,0-1-1H30V8a1.987,1.987,0,0,0-2.083-2A1.947,1.947,0,0,1,26,4V1a1,1,0,0,0-1-1H11a1,1,0,0,0-1,1V4A1.875,1.875,0,0,1,8,6Zm18,4V26H10V10Z"
  }));
}