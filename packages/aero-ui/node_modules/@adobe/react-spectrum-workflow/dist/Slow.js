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
exports.A4uSlow = A4uSlow;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSlow(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.117,10.673a2.883,2.883,0,0,0-2.883,2.883,3.843,3.843,0,0,0,1.036,2.107l-5.77,9.5c-.012,0,1.167-10.723,1.167-10.723,2.055.223,2.788-1.429,2.788-2.731a2.883,2.883,0,0,0-5.766,0A2.347,2.347,0,0,0,25.047,14L24,24H17.945A9.986,9.986,0,1,0,2,16c0,4.24,2.194,8.244,8.09,9.027-3.352,2.567-6.377,1.9-8.543,2.37C.131,27.7.712,30,2.162,30H31.691c1.652-.063.292-1.33-1.055-1.772-.827-.272-1.105-1.842-1.105-1.842-.242-.723-.968-1.184-1.523-1.653l4.527-8.482c.076.008.473.187.582.187a2.883,2.883,0,1,0,0-5.765Z"
  }));
}