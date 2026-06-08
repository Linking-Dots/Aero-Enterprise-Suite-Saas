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
exports.A4uWorkflow = A4uWorkflow;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uWorkflow(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "11.2",
    rx: "1",
    ry: "1",
    width: "8",
    x: "2",
    y: "12"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "6",
    rx: "1",
    ry: "1",
    width: "6",
    x: "28",
    y: "4"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "6",
    rx: "1",
    ry: "1",
    width: "6",
    x: "28",
    y: "14"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "6",
    rx: "1",
    ry: "1",
    width: "6",
    x: "28",
    y: "24"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M26,7.5v-1a.5.5,0,0,0-.5-.5h-7a.5.5,0,0,0-.5.5V16H12.5a.5.5,0,0,0-.5.5v1a.5.5,0,0,0,.5.5H18v9.5a.5.5,0,0,0,.5.5h7a.5.5,0,0,0,.5-.5v-1a.5.5,0,0,0-.5-.5H20V18h5.5a.5.5,0,0,0,.5-.5v-1a.5.5,0,0,0-.5-.5H20V8h5.5A.5.5,0,0,0,26,7.5Z"
  }));
}