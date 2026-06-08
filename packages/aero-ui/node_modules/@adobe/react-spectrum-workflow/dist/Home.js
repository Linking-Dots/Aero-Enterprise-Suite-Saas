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
exports.A4uHome = A4uHome;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uHome(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.332,20.25,18.75,3.668a1.063,1.063,0,0,0-1.5,0L.668,20.25a1.061,1.061,0,0,0,0,1.5l1.958,1.957A1,1,0,0,0,3.333,24H4v9a1,1,0,0,0,1,1h8a1,1,0,0,0,1-1V23a1,1,0,0,1,1-1h6a1,1,0,0,1,1,1V33a1,1,0,0,0,1,1h8a1,1,0,0,0,1-1V24h.667a1,1,0,0,0,.707-.293l1.958-1.957A1.061,1.061,0,0,0,35.332,20.25Z"
  }));
}