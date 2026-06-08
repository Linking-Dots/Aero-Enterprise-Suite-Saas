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
exports.A4uCut = A4uCut;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uCut(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M29.912,22.12c0-.007.035-.028.026-.028A8.481,8.481,0,0,1,22.8,18.074c-.017-.028-.046-.047-.065-.074.019-.027.048-.046.065-.074a8.481,8.481,0,0,1,7.142-4.018c.009,0-.023-.021-.026-.028a5.917,5.917,0,1,0-3.93-1.588l-6.47,3.444-12.6-6.7a4,4,0,0,0-3.8.023L.822,10.313,15.26,18,.822,25.687l2.292,1.255a4,4,0,0,0,3.8.023l12.6-6.7,6.47,3.444a5.892,5.892,0,1,0,3.93-1.588Zm.367-18.038a3.933,3.933,0,1,1-4.2,3.641A3.932,3.932,0,0,1,30.279,4.082Zm0,27.836a3.933,3.933,0,1,1,3.641-4.2A3.933,3.933,0,0,1,30.279,31.918Z"
  }));
}