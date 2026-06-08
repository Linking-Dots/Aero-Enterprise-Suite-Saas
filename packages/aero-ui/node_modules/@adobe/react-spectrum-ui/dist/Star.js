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
exports.Star = Star;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }

function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }

function Star(_ref) {
  var _ref$scale = _ref.scale,
      scale = _ref$scale === void 0 ? 'M' : _ref$scale,
      props = _objectWithoutProperties(_ref, ["scale"]);

  return _react["default"].createElement("svg", _extends({}, props, props), scale === 'L' && _react["default"].createElement("path", {
    d: "M11.361 1.68l2.259 5.975a.257.257 0 0 0 .228.166l6.381.3a.386.386 0 0 1 .223.686L15.467 12.8a.257.257 0 0 0-.087.268l1.684 6.162a.386.386 0 0 1-.584.424l-5.34-3.506a.257.257 0 0 0-.282 0l-5.34 3.506a.386.386 0 0 1-.584-.424l1.686-6.158a.257.257 0 0 0-.087-.268L1.548 8.809a.386.386 0 0 1 .223-.686l6.381-.3a.257.257 0 0 0 .228-.166l2.259-5.977a.386.386 0 0 1 .722 0z"
  }), scale === 'M' && _react["default"].createElement("path", {
    d: "M9.241.3l2.161 5.715 6.106.289a.255.255 0 0 1 .147.454l-4.77 3.823 1.612 5.9a.255.255 0 0 1-.386.28L9.002 13.4l-5.11 3.358a.255.255 0 0 1-.386-.28l1.612-5.9-4.77-3.821A.255.255 0 0 1 .495 6.3l6.107-.285L8.763.3a.255.255 0 0 1 .478 0z"
  }));
}

Star.displayName = 'Star';
