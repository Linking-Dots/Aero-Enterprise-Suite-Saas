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
exports.A4uFeature = A4uFeature;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uFeature(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18,2.2A15.8,15.8,0,1,0,33.8,18,15.8,15.8,0,0,0,18,2.2ZM30.2,14.774l-6.726,5.392,2.274,8.308a.355.355,0,0,1-.237.443.351.351,0,0,1-.306-.049L18,24.144l-7.206,4.731a.355.355,0,0,1-.543-.394l2.274-8.315L5.8,14.774a.355.355,0,0,1,.208-.639l8.61-.408,3.05-8.063a.355.355,0,0,1,.671,0l3.05,8.063,8.61.408a.355.355,0,0,1,.348.362A.351.351,0,0,1,30.2,14.774Z"
  }));
}