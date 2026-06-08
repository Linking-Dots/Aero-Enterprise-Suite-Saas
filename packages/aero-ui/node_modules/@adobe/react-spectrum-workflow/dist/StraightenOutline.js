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
exports.A4uStraightenOutline = A4uStraightenOutline;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uStraightenOutline(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.5,14H.5a.5.5,0,0,0-.5.5v13a.5.5,0,0,0,.5.5h33a.5.5,0,0,0,.5-.5v-13A.5.5,0,0,0,33.5,14ZM22.214,16l.018.968C22.232,19.05,18.9,20.1,17,20.1s-5.25-1.05-5.25-3.107V16ZM6,26H2V16H6Zm20,0H8V16h2v1c0,3,3.134,5,7,5s7-2,7-5V16h2Zm6,0H28V16h4Z"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "7",
    cy: "11",
    r: "1.3"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "27",
    cy: "11",
    r: "1.3"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "17",
    cy: "5",
    r: "1.3"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "11",
    cy: "7",
    r: "1.3"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "23",
    cy: "7",
    r: "1.3"
  }));
}