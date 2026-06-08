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
exports.A4uAirplane = A4uAirplane;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uAirplane(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M34.254.34094l-.6554.12793A9.57863,9.57863,0,0,0,28.6604,3.09705L22.238,9.51941,3.11975,4.3053a1.99991,1.99991,0,0,0-1.94043.51538L0,6l16.55835,9.1991L13.5979,18.15955a8.46931,8.46931,0,0,0-.87341,1.02441l-3.344,4.61975L1,23.42859l-1,1,6.36755,3.53747L4.34412,30.7616a.64041.64041,0,0,0,.89428.89428l2.79554-2.02343L11.57141,36l1-1-.37512-8.38049,4.61975-3.344a8.46931,8.46931,0,0,0,1.02441-.87341l2.96045-2.96045L30,36l1.17932-1.17932a2.00013,2.00013,0,0,0,.51526-1.94043L26.48059,13.762l6.42175-6.42175a9.58291,9.58291,0,0,0,2.629-4.9397l.12769-.65454A1.19765,1.19765,0,0,0,34.254.34094Z"
  }));
}