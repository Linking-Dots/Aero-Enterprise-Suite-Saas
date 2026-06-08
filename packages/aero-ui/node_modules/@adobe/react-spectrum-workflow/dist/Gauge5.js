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
exports.A4uGauge5 = A4uGauge5;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uGauge5(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M2.035,18.754A16,16,0,0,1,34,19.813c0,.072-.013.142-.014.214l-3.02-.327a12.126,12.126,0,0,0-.344-2.892,13.2,13.2,0,0,0-13.17-9.984A13.016,13.016,0,0,0,5,19.813a12.878,12.878,0,0,0,.691,4.117.492.492,0,0,1-.116.506L4.013,26a.5.5,0,0,1-.818-.154A15.842,15.842,0,0,1,2.035,18.754Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.154,23.214a.691.691,0,0,1,0,1.368L18.186,27.1a3.219,3.219,0,0,1-3.775-3.166h0a3.219,3.219,0,0,1,3.766-3.177Z"
  }));
}