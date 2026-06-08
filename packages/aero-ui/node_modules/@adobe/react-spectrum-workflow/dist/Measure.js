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
exports.A4uMeasure = A4uMeasure;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uMeasure(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M25.071,2.444,2.444,25.071a1,1,0,0,0,0,1.414l7.071,7.071a1,1,0,0,0,1.414,0l3.535-3.535-5.3-5.3a.5.5,0,0,1,0-.707l.707-.707a.5.5,0,0,1,.707,0l5.3,5.3,5.657-5.657-3.89-3.889a.5.5,0,0,1,0-.707l.708-.708a.5.5,0,0,1,.707,0l3.889,3.89,5.657-5.657-5.3-5.3a.5.5,0,0,1,0-.707l.707-.707a.5.5,0,0,1,.708,0l5.3,5.3,3.535-3.535a1,1,0,0,0,0-1.414L26.485,2.444A1,1,0,0,0,25.071,2.444Z"
  }));
}