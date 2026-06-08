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
exports.A4uEditInLight = A4uEditInLight;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uEditInLight(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M35.645,16.685l-4.324-4.323a.912.912,0,0,0-.65-.265h-.028a1.035,1.035,0,0,0-.7.3L14.711,27.639a.736.736,0,0,0-.188.315l-2.444,7.34c-.085.282.345.638.588.638a.231.231,0,0,0,.046-.005c.207-.048,6.26-2.118,7.344-2.444a.733.733,0,0,0,.31-.187L35.6,18.059a1.03,1.03,0,0,0,.3-.662A.916.916,0,0,0,35.645,16.685ZM14.039,33.973l1.978-5.519,3.54,3.531C17.936,32.472,15.439,33.555,14.039,33.973Z"
  }), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M27,2H3A1,1,0,0,0,2,3V27a1,1,0,0,0,1,1h9.077l.225-.678a2.7,2.7,0,0,1,.672-1.1L13.2,26H4V4H26v9.166l2-2V3A1,1,0,0,0,27,2Z"
  }));
}