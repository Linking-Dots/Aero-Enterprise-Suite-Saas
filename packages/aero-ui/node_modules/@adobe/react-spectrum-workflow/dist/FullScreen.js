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
exports.A4uFullScreen = A4uFullScreen;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uFullScreen(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32,24.5V30H26.5a.5.5,0,0,0-.5.5v1a.5.5,0,0,0,.5.5H34V24.5a.5.5,0,0,0-.5-.5h-1A.5.5,0,0,0,32,24.5Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M4,30V24.5a.5.5,0,0,0-.5-.5h-1a.5.5,0,0,0-.5.5V32H9.5a.5.5,0,0,0,.5-.5v-1a.5.5,0,0,0-.5-.5Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M26,4.5v1a.5.5,0,0,0,.5.5H32v5.5a.5.5,0,0,0,.5.5h1a.5.5,0,0,0,.5-.5V4H26.5A.5.5,0,0,0,26,4.5Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M4,6H9.5a.5.5,0,0,0,.5-.5v-1A.5.5,0,0,0,9.5,4H2v7.5a.5.5,0,0,0,.5.5h1a.5.5,0,0,0,.5-.5Z"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    height: "16",
    rx: "0.5",
    ry: "0.5",
    width: "20",
    x: "8",
    y: "10"
  }));
}