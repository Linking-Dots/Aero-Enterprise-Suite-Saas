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
exports.A4uSentimentPositive = A4uSentimentPositive;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSentimentPositive(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18,2A16,16,0,1,0,34,18,16,16,0,0,0,18,2ZM12.365,8.534c1.656,0,3,1.679,3,3.75s-1.344,3.75-3,3.75-3-1.679-3-3.75S10.708,8.534,12.365,8.534Zm11.108-.1c1.657,0,3,1.679,3,3.75s-1.343,3.75-3,3.75-3-1.679-3-3.75S21.816,8.432,23.473,8.432ZM18,28.04c-5.033,0-9.556-3.633-10-8.14H28C27.556,24.407,23.033,28.04,18,28.04Z"
  }));
}