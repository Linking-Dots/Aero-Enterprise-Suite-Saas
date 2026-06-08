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
exports.A4uReplyAll = A4uReplyAll;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uReplyAll(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M22.105,6H22V3a.733.733,0,0,0-.739-.735.718.718,0,0,0-.513.216L13.905,9.366a.735.735,0,0,0,0,.984l6.843,7.434a.718.718,0,0,0,.513.216A.733.733,0,0,0,22,17.265V14a12.429,12.429,0,0,1,12.179,4.785A.455.455,0,0,0,35,18.513C35,16.5,32.779,6,22.105,6Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M12.27,18.5H12V14.735A.733.733,0,0,0,11.261,14a.718.718,0,0,0-.513.216L2.189,22.508a.735.735,0,0,0,0,.984l8.559,8.292a.718.718,0,0,0,.513.216A.733.733,0,0,0,12,31.265V27.717c6.4-1.033,12.118,2.748,15,6.379a.555.555,0,0,0,1-.332C28,31.313,25.29,18.5,12.27,18.5Z"
  }));
}