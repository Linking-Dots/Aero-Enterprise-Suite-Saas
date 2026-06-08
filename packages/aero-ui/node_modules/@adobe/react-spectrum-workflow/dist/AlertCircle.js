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
exports.A4uAlertCircle = A4uAlertCircle;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uAlertCircle(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M18.1,2.2A15.9,15.9,0,1,0,34,18.1,15.9,15.9,0,0,0,18.1,2.2Zm0,29.812A13.912,13.912,0,1,1,32.012,18.1,13.912,13.912,0,0,1,18.1,32.012Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M15.69111,25.77237a2.26833,2.26833,0,0,1,2.23219-2.3039q.084-.00132.16781.00356a2.23208,2.23208,0,0,1,2.4,2.30034,2.18145,2.18145,0,0,1-2.4,2.23322,2.18156,2.18156,0,0,1-2.4-2.23322ZM20.125,8.79542a.41583.41583,0,0,1,.19932.36611v2.08271c0,2.80068-.56644,7.96068-.6661,8.96135,0,.09966-.03356.19933-.2339.19933H16.75789a.2214.2214,0,0,1-.2339-.19933c-.0661-.93356-.6-6.061-.6-8.86169V9.26119a.35461.35461,0,0,1,.16678-.36611,5.76552,5.76552,0,0,1,2.00034-.40067A6.5494,6.5494,0,0,1,20.125,8.79542Z"
  }));
}