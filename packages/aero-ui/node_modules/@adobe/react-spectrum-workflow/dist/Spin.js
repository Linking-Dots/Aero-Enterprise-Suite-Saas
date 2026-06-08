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
exports.A4uSpin = A4uSpin;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSpin(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M24,15v3.054c-6.836.185-7.634.254-9.648-.039-3.137-.451-6.837-1.968-6.952-3.968C7.257,12,9.47,9.918,12.517,8.894A16.148,16.148,0,0,1,16,8.133V16h4V3a1,1,0,0,0-1-1H17a1,1,0,0,0-1,1V6.022a18.64,18.64,0,0,0-4.167.672c-3.69,1.082-7.248,3.632-7.221,7.494.075,4.05,5.187,6.291,9.165,7.132,2.292.46,3.159.434,10.223.625V25a.5.5,0,0,0,.8.4L32,20l-7.2-5.4A.5.5,0,0,0,24,15Z"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "22.9",
    cy: "6.9",
    r: "1.1"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "26.968",
    cy: "7.371",
    r: "1.1"
  }), /*#__PURE__*/_react["default"].createElement("circle", {
    fillRule: "evenodd",
    cx: "30.9",
    cy: "8.9",
    r: "1.1"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M16,33a1,1,0,0,0,1,1h2a1,1,0,0,0,1-1V24H16Z"
  }));
}