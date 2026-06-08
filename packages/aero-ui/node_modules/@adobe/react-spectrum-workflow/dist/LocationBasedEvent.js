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
exports.A4uLocationBasedEvent = A4uLocationBasedEvent;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uLocationBasedEvent(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M20.5,14.054a.494.494,0,0,0-.5.5V34.336a.494.494,0,0,0,.846.353L26.51,29h8c.446,0,.479-.726.225-.98S20.846,14.2,20.846,14.2A.489.489,0,0,0,20.5,14.054Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M2,2V12.476A10.735,10.735,0,0,1,6,10.3L6,6H28V17.158l4,4V2Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M9,12.367a8.25,8.25,0,0,0-8.25,8.25C.75,25.173,9,35.57,9,35.57s8.25-10.4,8.25-14.953A8.25,8.25,0,0,0,9,12.367Zm0,11.75a3.5,3.5,0,1,1,3.5-3.5A3.5,3.5,0,0,1,9,24.117Z"
  }));
}