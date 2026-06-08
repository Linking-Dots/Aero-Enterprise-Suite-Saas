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
exports.A4uConversionFunnel = A4uConversionFunnel;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uConversionFunnel(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M10,24V35a1,1,0,0,0,1,1H23a1,1,0,0,0,1-1V24Zm11.975,4.2-5.053,6.738a.375.375,0,0,1-.565.04L12.7,31.326a.375.375,0,0,1,0-.53L14.3,29.2a.375.375,0,0,1,.53,0l1.512,1.512L19.575,26.4a.375.375,0,0,1,.525-.075l1.8,1.35A.375.375,0,0,1,21.975,28.2Z"
  }), /*#__PURE__*/_react["default"].createElement("polygon", {
    fillRule: "evenodd",
    points: "29 12 5 12 9.167 22 24.833 22 29 12"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.25,0H.75A.5.5,0,0,0,.288.692L4.167,10H29.833L33.712.692A.5.5,0,0,0,33.25,0Z"
  }));
}