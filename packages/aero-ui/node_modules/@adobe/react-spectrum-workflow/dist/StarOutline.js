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
exports.A4uStarOutline = A4uStarOutline;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uStarOutline(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18.059,5.082l3.554,9.5,10.219.481-7.974,6.4L26.529,31.3l-8.535-5.568L9.437,31.347l2.7-9.873-7.974-6.4,10.2-.489ZM18.082.823a.737.737,0,0,0-.7.479L12.971,12.651l-12.2.586a.75.75,0,0,0-.433,1.334l9.523,7.642-3.229,11.8a.752.752,0,0,0,.724.951.74.74,0,0,0,.41-.126L18,28.122,28.187,34.77a.742.742,0,0,0,.408.125.752.752,0,0,0,.725-.95L26.131,22.213l9.528-7.653a.75.75,0,0,0-.434-1.334l-12.2-.575-4.24-11.34A.738.738,0,0,0,18.082.823Z"
  }));
}