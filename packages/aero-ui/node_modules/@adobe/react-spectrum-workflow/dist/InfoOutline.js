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
exports.A4uInfoOutline = A4uInfoOutline;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uInfoOutline(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M20.15,12A2.15,2.15,0,1,1,18,9.85,2.15,2.15,0,0,1,20.15,12Zm.1835,12H20V16.3999A.4001.4001,0,0,0,19.60007,16H15.66648S14.5,16.03223,14.5,17c0,.96729,1.16651,1,1.16651,1H16v6h-.33349S14.5,24.03223,14.5,25c0,.96729,1.16651,1,1.16651,1h4.667S21.5,25.96729,21.5,25C21.5,24.03223,20.33347,24,20.33347,24ZM18,1A17,17,0,1,0,35.00008,18,17.00014,17.00014,0,0,0,18,1Zm0,30.34961A13.34961,13.34961,0,1,1,31.34967,18,13.34962,13.34962,0,0,1,18,31.34961Z"
  }));
}