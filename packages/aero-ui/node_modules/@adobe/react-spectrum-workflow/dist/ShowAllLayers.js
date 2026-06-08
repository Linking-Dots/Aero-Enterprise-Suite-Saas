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
exports.A4uShowAllLayers = A4uShowAllLayers;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uShowAllLayers(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M17.575,17.83,2.887,10.351c-.241-.123-.241-.323,0-.446l14.688-7.48a.943.943,0,0,1,.85,0L33.113,9.9c.241.123.241.323,0,.446L18.425,17.83A.936.936,0,0,1,17.575,17.83Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.114,25.905l-4.6-2.341L18,28.918,7.484,23.564l-4.6,2.341c-.241.123-.241.323,0,.446L17.575,33.83a.936.936,0,0,0,.85,0l14.689-7.479C33.354,26.228,33.354,26.028,33.114,25.905Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M33.114,17.905l-4.6-2.341L18,20.918,7.484,15.564l-4.6,2.341c-.241.123-.241.323,0,.446L17.575,25.83a.936.936,0,0,0,.85,0l14.689-7.479C33.354,18.228,33.354,18.028,33.114,17.905Z"
  }));
}