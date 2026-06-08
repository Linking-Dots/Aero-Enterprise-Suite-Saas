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
exports.A4uSegments = A4uSegments;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSegments(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M11.118,14H34.882A1.119,1.119,0,0,0,36,12.882V5.118A1.118,1.118,0,0,0,34.882,4H11.118A1.118,1.118,0,0,0,10,5.118V8H6a2,2,0,0,0-2,2v3.1a5,5,0,0,0,0,9.8V26a2,2,0,0,0,2,2h4v2.882A1.119,1.119,0,0,0,11.118,32H34.882A1.119,1.119,0,0,0,36,30.882V23.118A1.118,1.118,0,0,0,34.882,22H11.118A1.118,1.118,0,0,0,10,23.118V26H6V22.9a5,5,0,0,0,0-9.8V10h4v2.882A1.119,1.119,0,0,0,11.118,14ZM8,18a3,3,0,1,1-3-3A3,3,0,0,1,8,18Z"
  }));
}