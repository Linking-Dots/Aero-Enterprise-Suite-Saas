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
exports.A4uGraphAreaStacked = A4uGraphAreaStacked;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uGraphAreaStacked(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M30.371,16.321,34,23.578v9a1,1,0,0,1-1,1H3a1,1,0,0,1-1-1v-15l10,12L15.584,24.2a.5.5,0,0,1,.832,0L20,29.578l9.517-13.324A.5.5,0,0,1,30.371,16.321Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M11.769,25.239l2.151-3.227a2.5,2.5,0,0,1,4.16,0L20.074,25,27.98,13.933a2.5,2.5,0,0,1,4.271.335L34,17.765V7.578L30.43,2.223a.5.5,0,0,0-.84.012L20,17.578,16.416,12.2a.5.5,0,0,0-.832,0L12,17.578l-10-10v5.938Z"
  }));
}