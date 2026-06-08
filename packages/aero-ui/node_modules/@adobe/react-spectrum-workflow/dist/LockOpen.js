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
exports.A4uLockOpen = A4uLockOpen;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uLockOpen(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M29,16H11.9V10.352A6.213,6.213,0,0,1,18,4a6.142,6.142,0,0,1,5.507,3.419c.31.639.266,1.146.777,1.146a.508.508,0,0,0,.186-.036L27.152,7.46a.514.514,0,0,0,.322-.471C27.474,5.513,24.167.2,18,.2,11.143.2,8,6.505,8,10.292V16H7a1,1,0,0,0-1,1V33a1,1,0,0,0,1,1H29a1,1,0,0,0,1-1V17A1,1,0,0,0,29,16ZM20,26.222V29a1,1,0,0,1-1,1H17a1,1,0,0,1-1-1V26.222a3,3,0,1,1,4,0Z"
  }));
}