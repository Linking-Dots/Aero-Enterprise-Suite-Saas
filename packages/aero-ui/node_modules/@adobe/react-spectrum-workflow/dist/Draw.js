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
exports.A4uDraw = A4uDraw;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uDraw(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M20.454,8,5.084,23.372a.992.992,0,0,0-.251.421L2.055,33.1c-.114.376.459.85.783.85a.311.311,0,0,0,.062-.006c.276-.064,7.867-2.344,9.311-2.778a.984.984,0,0,0,.415-.25L28,15.544ZM11.4,29.316c-2.161.649-4.862,1.465-6.729,2.022l2.009-6.73Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.567,8.2,27.8,2.432a1.215,1.215,0,0,0-.866-.353H26.9a1.372,1.372,0,0,0-.927.407l-4.1,4.1,7.543,7.543,4.1-4.1a1.372,1.372,0,0,0,.4-.883A1.224,1.224,0,0,0,33.567,8.2Z"
  }));
}