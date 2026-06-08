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
exports.A4uTextSpaceAfter = A4uTextSpaceAfter;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uTextSpaceAfter(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "4",
    rx: "1",
    ry: "1",
    width: "20",
    x: "14",
    y: "8"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "4",
    rx: "1",
    ry: "1",
    width: "20",
    x: "14",
    y: "14"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "4",
    rx: "1",
    ry: "1",
    width: "20",
    x: "14",
    y: "2"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M4,33.328a.5.5,0,0,0,.866.341L10,28,4.866,22.331A.5.5,0,0,0,4,22.672Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M34,33V23a1,1,0,0,0-1-1H15a1,1,0,0,0-1,1V33a1,1,0,0,0,1,1H33A1,1,0,0,0,34,33Zm-2-1H16V24H32Z"
  }));
}