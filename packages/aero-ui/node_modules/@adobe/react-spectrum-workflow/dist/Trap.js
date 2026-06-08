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
exports.A4uTrap = A4uTrap;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uTrap(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M34.191,6.809a4.358,4.358,0,0,0-1.147-.727C31.026,5.232,22.787,1.8,18.426,1.253c-4.122-.515-7.858,0-9.791,1.932S7.99,10.4,9.794,14.136a75.205,75.205,0,0,0,4.041,6.989L2.662,32.3a2.065,2.065,0,0,0,.105,2.934,2.066,2.066,0,0,0,2.935.106L15.831,25.209a3.7,3.7,0,0,0,2.69.982,8.968,8.968,0,0,0,3.359-.768,26.846,26.846,0,0,0,7.391-5.211,26.708,26.708,0,0,0,5.152-7.332C35.523,10.213,35.439,8.057,34.191,6.809Zm-1.615,5.311a21.774,21.774,0,0,1-4.748,6.709,21.774,21.774,0,0,1-6.709,4.748c-1.813.75-3.272.824-3.9.2s-.547-2.078.2-3.9a21.774,21.774,0,0,1,4.748-6.709,21.774,21.774,0,0,1,6.709-4.748,7.133,7.133,0,0,1,2.6-.619,1.8,1.8,0,0,1,1.3.418C33.4,8.844,33.324,10.3,32.576,12.119Z"
  }));
}