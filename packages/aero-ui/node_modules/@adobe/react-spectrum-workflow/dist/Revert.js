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
exports.A4uRevert = A4uRevert;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uRevert(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "4",
    rx: "1",
    ry: "1",
    width: "32",
    x: "2",
    y: "26"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M2.5,20h10.75A.8.8,0,0,0,14,19.2a.784.784,0,0,0-.235-.56L9.81,14.681l.692-.693a11.447,11.447,0,0,1,19.116,5.074A1.215,1.215,0,0,0,30.78,20h1.894a1,1,0,0,0,.991-1.17A15.43,15.43,0,0,0,14.621,7.165,16.181,16.181,0,0,0,7.337,11.5l-.356.357L3.364,8.236A.781.781,0,0,0,2.8,8a.8.8,0,0,0-.8.754V19.5A.5.5,0,0,0,2.5,20Z"
  }));
}