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
exports.A4uCloneStamp = A4uCloneStamp;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uCloneStamp(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M20.647,21.62a29.989,29.989,0,0,1-.771-5.178,9.971,9.971,0,0,1,.612-2.945,5.755,5.755,0,0,0,3.631-5.748,6.111,6.111,0,1,0-12.222,0,5.748,5.748,0,0,0,3.611,5.744,10.467,10.467,0,0,1,.622,2.949,31.39,31.39,0,0,1-.777,5.179c-2.923.148-10,1.767-12.48,2.351A1.146,1.146,0,0,0,2,25.1v3.729A1.153,1.153,0,0,0,3.146,30l29.711,0A1.154,1.154,0,0,0,34,28.836V25.1a1.146,1.146,0,0,0-.873-1.131C30.651,23.388,23.573,21.769,20.647,21.62Z"
  }), /*#__PURE__*/_react["default"].createElement("rect", {
    fillRule: "evenodd",
    x: "4",
    y: "32",
    width: "28",
    height: "2",
    rx: "0.5"
  }));
}