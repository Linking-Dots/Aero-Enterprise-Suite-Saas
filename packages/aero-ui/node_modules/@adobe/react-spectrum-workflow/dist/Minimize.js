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
exports.A4uMinimize = A4uMinimize;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uMinimize(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32.077,2.707a1,1,0,0,0-1.414,0L24.179,9.191,21.2,6.206A.688.688,0,0,0,20.705,6a.7.7,0,0,0-.7.7v8.84a.5.5,0,0,0,.459.459H29.3a.7.7,0,0,0,.7-.7.685.685,0,0,0-.207-.49l-2.984-2.984,6.484-6.484a1,1,0,0,0,0-1.414Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M15.541,20H6.7a.7.7,0,0,0-.7.7.685.685,0,0,0,.207.49l2.984,2.984L2.707,30.663a1,1,0,0,0,0,1.414l1.216,1.216a1,1,0,0,0,1.414,0l6.484-6.484,2.984,2.985A.688.688,0,0,0,15.3,30,.7.7,0,0,0,16,29.3v-8.84A.5.5,0,0,0,15.541,20Z"
  }));
}