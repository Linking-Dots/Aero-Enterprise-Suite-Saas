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
exports.A4uShip = A4uShip;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uShip(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32,18,31.95349,4.99634a1,1,0,0,0-1-.99634H22V1a1,1,0,0,0-1-1H15a1,1,0,0,0-1,1V4H5A1,1,0,0,0,4,5V18l13.97327-2.99426ZM8,8H28v2H8Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.21692,21.82593,18,18l2,18,9.04358-.00012a.98894.98894,0,0,0,1-.84754C30.58508,30.1062,36,30.9624,36,26V22.80212A1.0001,1.0001,0,0,0,35.21692,21.82593Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M0,22.80212V26c0,4.9624,5.41492,4.1062,5.95642,9.15234a.98894.98894,0,0,0,1,.84754L18,36V18L.78308,21.82593A1.0001,1.0001,0,0,0,0,22.80212Z"
  }));
}