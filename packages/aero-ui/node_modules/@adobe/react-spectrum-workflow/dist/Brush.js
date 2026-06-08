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
exports.A4uBrush = A4uBrush;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uBrush(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M12.50879,21.03041A4.91741,4.91741,0,0,0,8.3144,22.23434a12.92375,12.92375,0,0,0-2.67981,4.78211C5.172,28.95625,4.73525,30.788,2.27511,31.7883a.59927.59927,0,0,0-.3407.71187.89787.89787,0,0,0,.64413.65762c.58521.14594,1.25345.28416,1.97712.39787,2.60758.40972,7.48017.738,10.80656-1.6515,1.23744-.84779,2.83646-2.98169,2.82212-4.54659A6.813,6.813,0,0,0,12.50879,21.03041Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M19.9,24.1C27.13475,15.87336,36.32182,4.565,33.91572,2.15888S21.546,10.97629,14.38,18.83A10.05145,10.05145,0,0,1,19.9,24.1Z"
  }));
}