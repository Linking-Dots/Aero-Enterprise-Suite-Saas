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
exports.A4uCameraFlip = A4uCameraFlip;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uCameraFlip(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33,8H25.95L22.6,4.326A1,1,0,0,0,21.859,4H12.141a1,1,0,0,0-.739.326L8.05,8H1A1,1,0,0,0,0,9V29a1,1,0,0,0,1,1H33a1,1,0,0,0,1-1V9A1,1,0,0,0,33,8ZM17,26.2a8.141,8.141,0,0,1-5.782-2.418L9.853,25.147A.5.5,0,0,1,9,24.793V20.5a.5.5,0,0,1,.5-.5h4.293a.5.5,0,0,1,.353.854l-1.364,1.364A5.907,5.907,0,0,0,17,24a5.985,5.985,0,0,0,5.51-3.688A.5.5,0,0,1,22.965,20h1.291a.5.5,0,0,1,.48.643A8.178,8.178,0,0,1,17,26.2Zm8-10.7a.5.5,0,0,1-.5.5H20.207a.5.5,0,0,1-.354-.853l1.365-1.365A5.907,5.907,0,0,0,17,12a5.986,5.986,0,0,0-5.51,3.688.5.5,0,0,1-.455.312H9.744a.5.5,0,0,1-.48-.642,8.148,8.148,0,0,1,13.518-3.14l1.364-1.364a.5.5,0,0,1,.854.353Z"
  }));
}