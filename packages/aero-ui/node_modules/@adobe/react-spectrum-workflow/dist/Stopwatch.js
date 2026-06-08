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
exports.A4uStopwatch = A4uStopwatch;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uStopwatch(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M20,2h1a1,1,0,0,0,0-2H17a1,1,0,0,0,0,2h1V4h2Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M19,4A14.94,14.94,0,0,0,9.1,7.729L7.437,6.062l.708-.707A1,1,0,1,0,6.73,3.941l-.707.707L4.609,6.062,3.9,6.77A1,1,0,0,0,5.316,8.184l.707-.707L7.692,9.145A15,15,0,1,0,19,4Zm0,28A13,13,0,1,1,26.833,8.625L17.908,17.55c-.021.021-.037.04-.057.062a1.858,1.858,0,1,0,2.619,2.635c.023-.021.046-.045.068-.067l8.913-8.912A13,13,0,0,1,19,32Z"
  }));
}