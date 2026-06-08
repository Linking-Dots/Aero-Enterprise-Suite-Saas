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
exports.A4uSelectBox = A4uSelectBox;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uSelectBox(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M29.2,2H6.8A4.8,4.8,0,0,0,2,6.8V29.2A4.8,4.8,0,0,0,6.8,34H29.2A4.8,4.8,0,0,0,34,29.2V6.8A4.8,4.8,0,0,0,29.2,2Zm-.355,10.377L14.566,26.655a.8.8,0,0,1-1.131,0l-6.28-6.278a.8.8,0,0,1,0-1.131l2.491-2.491a.8.8,0,0,1,1.131,0L14,19.98,25.223,8.755a.8.8,0,0,1,1.131,0l2.491,2.491A.8.8,0,0,1,28.845,12.377Z"
  }));
}