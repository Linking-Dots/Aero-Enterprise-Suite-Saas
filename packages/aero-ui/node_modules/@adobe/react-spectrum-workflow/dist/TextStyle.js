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
exports.A4uTextStyle = A4uTextStyle;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uTextStyle(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M7.976,23.3c.584,3.042,2.97,8.479,8.486,8.479,3.818,0,5.728-2.442,5.728-5.069,0-2.165-1.485-4.055-4.19-5.9L16.409,19.8c-3.341-2.258-6.311-4.838-6.311-8.663,0-5.438,5.038-8.8,11.561-8.8a19.74,19.74,0,0,1,5.993.922c.955.276,1.644.553,2.174.737a63.223,63.223,0,0,0-.318,7.051l-1.856.138c-.477-2.9-1.75-7-6.417-7a4.806,4.806,0,0,0-5.091,4.747c0,2.258,1.485,3.733,4.3,5.484l1.591.967c3.66,2.3,6.683,4.839,6.683,8.986,0,5.807-5.728,9.309-12.834,9.309-4.4,0-8.115-1.567-9.653-2.857.053-1.06.053-3.641,0-7.327Z"
  }));
}