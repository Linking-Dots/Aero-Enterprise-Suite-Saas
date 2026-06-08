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
exports.A4uShapes = A4uShapes;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uShapes(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M22.52148,31.80078a11.30761,11.30761,0,0,1-11.05273-9.02344l-.03127-.16015H1.73438a.25611.25611,0,0,1-.22461-.13086.24871.24871,0,0,1,0-.25586L11.73633,4.33008a.26013.26013,0,0,1,.44922,0l3.9414,6.89844.17969-.11914a11.2791,11.2791,0,1,1,6.21484,20.6914Zm-9.08593-8.93359a9.3813,9.3813,0,1,0,3.78906-10.08985l-.15234.10352,5.34179,9.34961a.24876.24876,0,0,1,0,.25586.25613.25613,0,0,1-.22461.13086H13.37109Z"
  }));
}