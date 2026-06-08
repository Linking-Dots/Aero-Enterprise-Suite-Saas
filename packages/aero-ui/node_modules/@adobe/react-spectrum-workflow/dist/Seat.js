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
exports.A4uSeat = A4uSeat;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSeat(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M5,18H4a2,2,0,0,0-2,2V33a1,1,0,0,0,1,1H5a1,1,0,0,0,1-1V19A1,1,0,0,0,5,18Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32,18H31a1,1,0,0,0-1,1V33a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V20A2,2,0,0,0,32,18Z"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "8",
    rx: "1",
    ry: "1",
    width: "20",
    x: "8",
    y: "22"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M22,4H14a6,6,0,0,0-6,6v9a1,1,0,0,0,1,1H27a1,1,0,0,0,1-1V10A6,6,0,0,0,22,4Z"
  }));
}