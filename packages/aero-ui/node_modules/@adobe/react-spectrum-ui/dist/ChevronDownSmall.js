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
exports.ChevronDownSmall = ChevronDownSmall;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }

function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }

function ChevronDownSmall(_ref) {
  var _ref$scale = _ref.scale,
      scale = _ref$scale === void 0 ? 'M' : _ref$scale,
      props = _objectWithoutProperties(_ref, ["scale"]);

  return _react["default"].createElement("svg", _extends({}, props, props), scale === 'L' && _react["default"].createElement("path", {
    d: "M5 7a.747.747 0 0 0 .53-.22l4.24-4.24a.75.75 0 1 0-1.06-1.06L5 5.19 1.29 1.48A.75.75 0 1 0 .23 2.54l4.24 4.24A.747.747 0 0 0 5 7z"
  }), scale === 'M' && _react["default"].createElement("path", {
    d: "M4 5.5a.747.747 0 0 0 .53-.22c.607-.577 1.97-2.038 3.24-3.24A.75.75 0 1 0 6.71.98L4 3.69 1.29.98A.75.75 0 1 0 .23 2.04l3.24 3.24A.747.747 0 0 0 4 5.5z"
  }));
}

ChevronDownSmall.displayName = 'ChevronDownSmall';
