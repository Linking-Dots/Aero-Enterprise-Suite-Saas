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
exports.A4uColorPalette = A4uColorPalette;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uColorPalette(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M23.61385,6.1449c-4.37056-.6954-9.00542,0-9.64823,2.09184A2.29291,2.29291,0,0,0,15.26,11.14533c1.15183.64672,2.60169,2.67267,1.13858,4.54045a2.82733,2.82733,0,0,1-3.12437,1.12583c-3.7482-.94636-7.8929-2.88115-11.28533.3453C-1.10345,20.098.15778,24.46581,3.15355,26.8422a23.403,23.403,0,0,0,14.5132,5.27359C27.25261,32.11579,35.8,26.46483,35.8,19,35.8,11.4422,28.63156,6.94325,23.61385,6.1449ZM8.69365,27.4531a3.799,3.799,0,1,1,3.7982-3.79895A3.79857,3.79857,0,0,1,8.69365,27.4531Zm19.2861-16.03385a2.5005,2.5005,0,1,1-2.5,2.5005A2.50024,2.50024,0,0,1,27.97975,11.41925ZM17.2777,29.55871a3.56019,3.56019,0,1,1,3.55945-3.56016A3.55983,3.55983,0,0,1,17.2777,29.55871Zm7.79018-1.49557a3.005,3.005,0,1,1,3.00433-3.005A3.00469,3.00469,0,0,1,25.06788,28.06314ZM30,22.56a2.67467,2.67467,0,1,1,2.67414-2.67467A2.67442,2.67442,0,0,1,30,22.56Z"
  }));
}