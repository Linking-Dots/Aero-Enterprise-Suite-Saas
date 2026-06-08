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
exports.A4uDropdown = A4uDropdown;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uDropdown(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M30.5,2H3.5A1.5,1.5,0,0,0,2,3.5V8.463a1.5,1.5,0,0,0,1.5,1.5h27a1.5,1.5,0,0,0,1.5-1.5V3.5A1.5,1.5,0,0,0,30.5,2ZM25,8.764,21.28,4.726a.432.432,0,0,1,.332-.708H28.4a.432.432,0,0,1,.332.708Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M30.5,12H3.5A1.5,1.5,0,0,0,2,13.5v19A1.5,1.5,0,0,0,3.5,34h27A1.5,1.5,0,0,0,32,32.5v-19A1.5,1.5,0,0,0,30.5,12ZM6,15.75A.75.75,0,0,1,6.75,15h20.5a.75.75,0,0,1,.75.75v1.5a.75.75,0,0,1-.75.75H6.75A.75.75,0,0,1,6,17.25Zm22,13.5a.75.75,0,0,1-.75.75H6.75A.75.75,0,0,1,6,29.25v-1.5A.75.75,0,0,1,6.75,27h20.5a.75.75,0,0,1,.75.75Zm-2-6a.75.75,0,0,1-.75.75H6.75A.75.75,0,0,1,6,23.25v-1.5A.75.75,0,0,1,6.75,21h18.5a.75.75,0,0,1,.75.75Z"
  }));
}