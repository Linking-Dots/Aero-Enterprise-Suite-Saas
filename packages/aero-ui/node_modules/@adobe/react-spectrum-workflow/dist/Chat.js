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
exports.A4uChat = A4uChat;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uChat(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M19,14a1,1,0,0,1,1,1V27a1,1,0,0,1-1,1H9.586a1,1,0,0,0-.707.293L6,31.171V29a1,1,0,0,0-1-1H3a1,1,0,0,1-1-1V15a1,1,0,0,1,1-1ZM3,12a3,3,0,0,0-3,3V27a3,3,0,0,0,3,3H4v4.793a.5.5,0,0,0,.854.353L10,30h9a3,3,0,0,0,3-3V15a3,3,0,0,0-3-3Z",
    transform: "translate(0)"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M24,14.6A4.6,4.6,0,0,0,19.4,10H12V5a3,3,0,0,1,3-3H33a3,3,0,0,1,3,3V17a3,3,0,0,1-3,3H30v4.793a.5.5,0,0,1-.854.353L24,20Z",
    transform: "translate(0)"
  }));
}