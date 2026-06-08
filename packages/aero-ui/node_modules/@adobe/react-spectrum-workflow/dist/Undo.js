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
exports.A4uUndo = A4uUndo;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uUndo(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M30.663,12.542A10.391,10.391,0,0,0,23.671,10L11,10V4.8a.8.8,0,0,0-.8-.8.787.787,0,0,0-.527.2L2.144,11.649a.5.5,0,0,0,0,.7L9.668,19.8a.787.787,0,0,0,.527.2.8.8,0,0,0,.8-.8V14H23.877A6.139,6.139,0,0,1,30.1,19.8,5.889,5.889,0,0,1,24,26H17a1,1,0,0,0-1,1v2a1,1,0,0,0,1,1h6.526a10.335,10.335,0,0,0,10.426-9.013A9.947,9.947,0,0,0,30.663,12.542Z"
  }));
}