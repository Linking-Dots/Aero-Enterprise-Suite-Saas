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
exports.A4uCloudOutline = A4uCloudOutline;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uCloudOutline(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M20.5,6.714a6.788,6.788,0,0,1,6.538,8.606,5.492,5.492,0,0,1,.605-.034,5.357,5.357,0,0,1,0,10.714H6.214a3.215,3.215,0,0,1,0-6.429h.359s0-.918,0-1.428a5.718,5.718,0,0,1,7.2-5.519A6.788,6.788,0,0,1,20.5,6.714Zm0-2a8.811,8.811,0,0,0-8.233,5.715,7.724,7.724,0,0,0-7.69,7.406A5.214,5.214,0,0,0,6.214,28H27.643a7.357,7.357,0,0,0,1.643-14.529A8.8,8.8,0,0,0,20.5,4.714Z"
  }));
}