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
exports.A4uDeviceRotateLandscape = A4uDeviceRotateLandscape;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uDeviceRotateLandscape(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M15.158,30H8V6H24v9.21a12.3,12.3,0,0,1,2-.354V2a2,2,0,0,0-2-2H8A2,2,0,0,0,6,2V34a2,2,0,0,0,2,2H18.625A12.27,12.27,0,0,1,15.158,30ZM15,2h2a1.04,1.04,0,0,1,1,1,1.04,1.04,0,0,1-1,1H15a1.023,1.023,0,0,1-1-1A1.024,1.024,0,0,1,15,2Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32.412,20.332l1.479-1.478a.489.489,0,0,0,.147-.35.5.5,0,0,0-.5-.5H28.485a.5.5,0,0,0-.447.448V23.5a.5.5,0,0,0,.5.5.489.489,0,0,0,.35-.147l1.5-1.506a6.015,6.015,0,0,1,2.144,5.6,6.074,6.074,0,1,1-8.123-6.615.976.976,0,0,0,.62-.921V19.156a.76.76,0,0,0-.974-.723,8.919,8.919,0,0,0-6.451,8.552,9.02,9.02,0,0,0,8.645,8.936,8.891,8.891,0,0,0,6.154-15.589Z"
  }));
}