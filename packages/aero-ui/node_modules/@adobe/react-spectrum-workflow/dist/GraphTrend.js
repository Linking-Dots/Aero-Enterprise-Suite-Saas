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
exports.A4uGraphTrend = A4uGraphTrend;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uGraphTrend(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.093,6.061l-8.14,11.374L20.9,9.321a.5.5,0,0,0-.917.053l-5.45,14.992-4.081-4.081a.5.5,0,0,0-.674-.031L2.18,26.579a.5.5,0,0,0-.18.384v4.188a.5.5,0,0,0,.829.376l7.048-6.157,5.708,5.708a.5.5,0,0,0,.823-.183l4.548-12.51L24,24.481a.5.5,0,0,0,.857.063L33.91,11.616A.5.5,0,0,0,34,11.33V6.352A.5.5,0,0,0,33.093,6.061Z"
  }));
}