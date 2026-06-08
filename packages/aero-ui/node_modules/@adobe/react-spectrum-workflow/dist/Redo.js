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
exports.A4uRedo = A4uRedo;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uRedo(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M5.337,12.542A10.391,10.391,0,0,1,12.329,10L25,10V4.8a.8.8,0,0,1,.8-.8.787.787,0,0,1,.527.2l7.524,7.445a.5.5,0,0,1,0,.7L26.332,19.8a.787.787,0,0,1-.527.2.8.8,0,0,1-.8-.8V14H12.123A6.139,6.139,0,0,0,5.9,19.8,5.889,5.889,0,0,0,12,26h7a1,1,0,0,1,1,1v2a1,1,0,0,1-1,1H12.474A10.335,10.335,0,0,1,2.048,20.987,9.947,9.947,0,0,1,5.337,12.542Z"
  }));
}