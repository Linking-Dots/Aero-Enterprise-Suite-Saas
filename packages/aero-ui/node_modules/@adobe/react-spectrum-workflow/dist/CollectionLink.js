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
exports.A4uCollectionLink = A4uCollectionLink;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uCollectionLink(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M15.136,28H14V18h8v1.208l1.937-1.937L25.207,16H24V6h8v7.063a7.552,7.552,0,0,1,2,.428V5a1,1,0,0,0-1-1H3A1,1,0,0,0,2,5V29a1,1,0,0,0,1,1H15.065A7.664,7.664,0,0,1,15.136,28ZM14,6h8V16H14ZM12,28H4V18h8Zm0-12H4V6h8Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M25.548,25.421a2.165,2.165,0,0,0,.421.611,2.19,2.19,0,0,0,3.094,0l3.609-3.609a2.188,2.188,0,0,0-3.094-3.094l-.819.819A5.85,5.85,0,0,0,26.11,19.7l1.921-1.921a4.375,4.375,0,0,1,6.187,6.187l-3.609,3.609a4.351,4.351,0,0,1-6.656-.562Zm-2.157-3-3.609,3.609a4.375,4.375,0,0,0,6.187,6.187L27.89,30.3a5.851,5.851,0,0,1-2.649-.445l-.819.819a2.188,2.188,0,0,1-3.094-3.094l3.609-3.609a2.19,2.19,0,0,1,3.094,0,2.157,2.157,0,0,1,.421.611l1.6-1.6a4.351,4.351,0,0,0-6.656-.562Z"
  }));
}