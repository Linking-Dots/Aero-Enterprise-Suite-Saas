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
exports.A4uAudio = A4uAudio;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uAudio(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M30,3.417a1,1,0,0,0-1.268-.965l-16,4.447A1,1,0,0,0,12,7.863v16.55a6.628,6.628,0,0,0-6.144.057c-3.113,1.515-4.687,4.7-3.515,7.1S6.987,34.706,10.1,33.19A6.434,6.434,0,0,0,14,27.857c0-.167,0-15.033,0-15.033l14-4V20.413a6.628,6.628,0,0,0-6.144.057c-3.113,1.515-4.687,4.7-3.515,7.1s4.646,3.132,7.759,1.616A6.427,6.427,0,0,0,30,23.833C30,23.767,30,3.417,30,3.417Z"
  }));
}