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
exports.A4uData = A4uData;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uData(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("ellipse", {
    fillRule: "evenodd",
    cx: "18",
    cy: "7",
    rx: "16",
    ry: "5"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18,24.275c-4.936,0-14.212-1.169-16-4V29c0,2.761,7.163,5,16,5s16-2.239,16-5V20.27C31.553,23.365,22.936,24.275,18,24.275Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18,14.275c-4.936,0-14.212-1.169-16-4.005V17c0,2.761,7.163,5,16,5s16-2.239,16-5V10.27C31.553,13.365,22.936,14.275,18,14.275Z"
  }));
}