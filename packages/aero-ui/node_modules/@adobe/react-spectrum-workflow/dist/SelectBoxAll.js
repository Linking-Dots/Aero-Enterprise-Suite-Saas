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
exports.A4uSelectBoxAll = A4uSelectBoxAll;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSelectBoxAll(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M29.2,8H12.8A4.8,4.8,0,0,0,8,12.8V29.2A4.8,4.8,0,0,0,12.8,34H29.2A4.8,4.8,0,0,0,34,29.2V12.8A4.8,4.8,0,0,0,29.2,8Zm1.223,9.049L18.988,28.573a.8.8,0,0,1-1.131,0l-6.28-6.278a.8.8,0,0,1,0-1.131l2.491-2.491a.8.8,0,0,1,1.131,0L18.423,21.9l8.378-8.47a.8.8,0,0,1,1.131,0l2.491,2.491A.8.8,0,0,1,30.423,17.049Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M26,2H6.8A4.8,4.8,0,0,0,2,6.8V26a4,4,0,0,0,4,4H6V6H30A4,4,0,0,0,26,2Z"
  }));
}