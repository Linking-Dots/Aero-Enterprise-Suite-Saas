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
exports.A4uImageMapCircle = A4uImageMapCircle;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uImageMapCircle(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32,10.461V4.5a.5.5,0,0,0-.5-.5H25.539A15.907,15.907,0,0,0,10.461,4H4.5a.5.5,0,0,0-.5.5v5.961A15.906,15.906,0,0,0,4,25.539V31.5a.5.5,0,0,0,.5.5h5.961a15.907,15.907,0,0,0,15.078,0H31.5a.5.5,0,0,0,.5-.5V25.539a15.906,15.906,0,0,0,0-15.079ZM26,6h4v4H26ZM6,6h4v4H6Zm4,24H6V26h4Zm20,0H26V26h4Zm.537-6H24.5a.5.5,0,0,0-.5.5v6.038a13.778,13.778,0,0,1-12,0V24.5a.5.5,0,0,0-.5-.5H5.463a13.778,13.778,0,0,1,0-12H11.5a.5.5,0,0,0,.5-.5V5.462a13.778,13.778,0,0,1,12,0V11.5a.5.5,0,0,0,.5.5h6.037a13.778,13.778,0,0,1,0,12Z"
  }));
}