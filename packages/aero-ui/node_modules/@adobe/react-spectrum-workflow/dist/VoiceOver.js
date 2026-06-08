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
exports.A4uVoiceOver = A4uVoiceOver;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uVoiceOver(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M23.8,7.2a6.8,6.8,0,0,0-13.6,0V20.8a6.8,6.8,0,1,0,13.6,0Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M28,21V16.5a.5.5,0,0,0-.5-.5h-1a.5.5,0,0,0-.5.5V21A9,9,0,1,1,8,21V16.5a.5.5,0,0,0-.5-.5h-1a.5.5,0,0,0-.5.5V21c0,5.725,5.357,11,10,11v2H8.5a.5.5,0,0,0-.5.5v1a.5.5,0,0,0,.5.5h17a.5.5,0,0,0,.5-.5v-1a.5.5,0,0,0-.5-.5H18V31.942C22.643,31.942,28,26.726,28,21Z"
  }));
}