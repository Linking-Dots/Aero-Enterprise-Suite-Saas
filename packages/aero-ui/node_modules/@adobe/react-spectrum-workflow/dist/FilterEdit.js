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
exports.A4uFilterEdit = A4uFilterEdit;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uFilterEdit(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.785,21.721,32.28,18.215A.739.739,0,0,0,31.753,18h-.023a.833.833,0,0,0-.564.247L20.344,29.069a.608.608,0,0,0-.153.256l-2.027,6c-.069.229.279.517.476.517a.313.313,0,0,0,.037,0c.168-.039,5.123-1.764,6-2.028a.6.6,0,0,0,.252-.151L35.753,22.834A.835.835,0,0,0,36,22.3.743.743,0,0,0,35.785,21.721Zm-11.6,10.963c-1.314.395-3.3,1.229-4.43,1.568l1.56-4.431Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M30.946,2H3.054a1,1,0,0,0-.787,1.617L14,18.589V30a.992.992,0,0,0,1.68.825l3.98-4.153a1.22,1.22,0,0,0,.34-.845V18.589L31.733,3.617A1,1,0,0,0,30.946,2Z"
  }));
}