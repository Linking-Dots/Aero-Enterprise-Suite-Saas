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
exports.A4uZoomIn = A4uZoomIn;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uZoomIn(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M21.5,14H18V10.5a.5.5,0,0,0-.5-.5h-3a.5.5,0,0,0-.5.5V14H10.5a.5.5,0,0,0-.5.5v3a.5.5,0,0,0,.5.5H14v3.5a.5.5,0,0,0,.5.5h3a.5.5,0,0,0,.5-.5V18h3.5a.5.5,0,0,0,.5-.5v-3A.5.5,0,0,0,21.5,14Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.173,32.215,27.256,24.3A14.031,14.031,0,1,0,24.3,27.257l7.916,7.916a2.1,2.1,0,0,0,2.958-2.958ZM6,16A10,10,0,1,1,16,26,10,10,0,0,1,6,16Z"
  }));
}