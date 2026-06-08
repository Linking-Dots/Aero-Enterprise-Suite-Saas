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
exports.A4uReport = A4uReport;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uReport(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M27,4H9A1,1,0,0,0,8,5V31a1,1,0,0,0,1,1H27a1,1,0,0,0,1-1V5A1,1,0,0,0,27,4ZM16,10.5a.5.5,0,0,1,.5-.5h3a.5.5,0,0,1,.5.5v7a.5.5,0,0,1-.5.5h-3a.5.5,0,0,1-.5-.5Zm-6,4a.5.5,0,0,1,.5-.5h3a.5.5,0,0,1,.5.5v3a.5.5,0,0,1-.5.5h-3a.5.5,0,0,1-.5-.5Zm12,15a.5.5,0,0,1-.5.5h-11a.5.5,0,0,1-.5-.5v-3a.5.5,0,0,1,.5-.5h11a.5.5,0,0,1,.5.5Zm4-6a.5.5,0,0,1-.5.5h-15a.5.5,0,0,1-.5-.5v-3a.5.5,0,0,1,.5-.5h15a.5.5,0,0,1,.5.5Zm0-6a.5.5,0,0,1-.5.5h-3a.5.5,0,0,1-.5-.5V6.5a.5.5,0,0,1,.5-.5h3a.5.5,0,0,1,.5.5Z"
  }));
}