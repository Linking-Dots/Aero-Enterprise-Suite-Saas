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
exports.A4uChannel = A4uChannel;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uChannel(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M32.375,15.125A2.864,2.864,0,0,0,29.9,16.562H25.355a7.466,7.466,0,0,0-2.67-4.376l2.62-4.979A2.879,2.879,0,1,0,22.76,5.871l-2.619,4.977A7.4,7.4,0,0,0,18,10.5a7.4,7.4,0,0,0-2.141.348L13.24,5.871a2.864,2.864,0,0,0-2.427-4.4A2.87,2.87,0,0,0,10.7,7.207l2.62,4.979a7.466,7.466,0,0,0-2.67,4.376H6.1a2.875,2.875,0,1,0,0,2.876h4.544a7.466,7.466,0,0,0,2.67,4.376L10.7,28.793a2.881,2.881,0,1,0,2.545,1.336l2.619-4.977A7.4,7.4,0,0,0,18,25.5a7.4,7.4,0,0,0,2.141-.348l2.619,4.977a2.865,2.865,0,0,0,2.427,4.4,2.87,2.87,0,0,0,.118-5.738l-2.62-4.979a7.466,7.466,0,0,0,2.67-4.376H29.9a2.87,2.87,0,1,0,2.476-4.313ZM18,22.575A4.575,4.575,0,1,1,22.575,18,4.575,4.575,0,0,1,18,22.575Z"
  }));
}