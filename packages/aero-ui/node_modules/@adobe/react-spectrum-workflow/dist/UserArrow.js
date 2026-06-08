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
exports.A4uUserArrow = A4uUserArrow;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uUserArrow(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M10.874,19.622a.5.5,0,0,0-.874.332V24H3a1,1,0,0,0-1,1v4a1,1,0,0,0,1,1h7v3.818a.5.5,0,0,0,.874.332L18,27Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M26.255,19.775a1.438,1.438,0,0,1-1.244-1.443V16.249a1.441,1.441,0,0,1,.367-.93,11,11,0,0,0,2.5-6.866c0-5.2-2.756-8.1-6.919-8.1s-7,3.018-7,8.1a11.124,11.124,0,0,0,2.645,6.893,1.388,1.388,0,0,1,.344.9v2.126a1.4,1.4,0,0,1-1.368,1.394L22.569,27l-2.99,3H35.936c.006-.731.011-1.417.011-1.526C35.947,26.311,34.469,20.609,26.255,19.775Z"
  }));
}