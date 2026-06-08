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
exports.A4uBreadcrumbNavigation = A4uBreadcrumbNavigation;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uBreadcrumbNavigation(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.99878,17.99973,27.97727,27.469a1.50017,1.50017,0,0,1-1.14466.53051H22.6068a.5.5,0,0,1-.3815-.82317l7.77364-9.17656L22.2253,8.82316A.5.5,0,0,1,22.6068,8h4.226a1.5,1.5,0,0,1,1.14457.53047Zm-9.99973,0L17.97754,27.469a1.49956,1.49956,0,0,1-1.1445.53051H1.00071a1,1,0,0,1-1-1L-.00024,9a1,1,0,0,1,1-1H16.833a1.49944,1.49944,0,0,1,1.1445.53038Zm-18.49852,0A2.49994,2.49994,0,1,0,5.0006,20.49966,2.49982,2.49982,0,0,0,7.50053,17.99973Zm6.49982,0a2.49993,2.49993,0,1,0-2.49993,2.49993A2.49982,2.49982,0,0,0,14.00035,17.99973Zm6.49983,0a2.49994,2.49994,0,1,0-2.49994,2.49993A2.49982,2.49982,0,0,0,20.50018,17.99973Z"
  }));
}