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
exports.A4uHammer = A4uHammer;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uHammer(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M11.591,4.066l-5.08,5.08a1.455,1.455,0,0,0,0,2.063l.344.33-1.51,1.573a.968.968,0,0,0-1.392-.041l-1.55,1.55a.727.727,0,0,0,0,1.03l4.109,4.108a.726.726,0,0,0,1.029,0l1.55-1.55c.569-.568-.023-1.374-.023-1.374L10.662,15.3a1.457,1.457,0,0,0,2.046-.013l.866-.867L30.443,31.289a1.455,1.455,0,0,0,2.059,0l1.366-1.366a1.455,1.455,0,0,0,0-2.059L17,11l.565-.565a1.456,1.456,0,0,0,0-2.058l-.684-.684s2.012-2.257,2.434-2.68c1.777-1.777,5.711-.631,5.893-1.541S16.472-.815,11.591,4.066Z"
  }));
}