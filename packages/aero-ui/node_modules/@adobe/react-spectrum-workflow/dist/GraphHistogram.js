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
exports.A4uGraphHistogram = A4uGraphHistogram;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uGraphHistogram(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.5,30h-3a.5.5,0,0,0-.5.5v-4a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5v-6a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5v-8a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5V6.519A.519.519,0,0,0,17.481,6H14.519A.519.519,0,0,0,14,6.519V10.5a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5v10a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5v8a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5V34H34V30.5A.5.5,0,0,0,33.5,30Z"
  }));
}