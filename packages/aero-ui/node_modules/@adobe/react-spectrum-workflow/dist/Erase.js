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
exports.A4uErase = A4uErase;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uErase(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18.613,28.132a1,1,0,0,0,1.414,0L33.589,14.571a1,1,0,0,0,0-1.414L22.275,1.843a1,1,0,0,0-1.414,0L7.3,15.4a1,1,0,0,0,0,1.414l.707.707-6.3,6.3a2,2,0,0,0,0,2.829l6.505,6.5A2.8,2.8,0,0,0,10.133,34c.107,0,.3,0,.3,0H33.5a.5.5,0,0,0,.5-.5v-1a.5.5,0,0,0-.5-.5H13.331l4.575-4.575ZM10.9,31.607a1,1,0,0,1-1.414,0L3.118,25.243l6.3-6.3,7.071,7.071Z"
  }));
}