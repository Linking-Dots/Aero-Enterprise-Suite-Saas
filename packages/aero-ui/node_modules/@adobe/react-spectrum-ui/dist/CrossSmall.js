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
exports.CrossSmall = CrossSmall;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function _objectWithoutProperties(source, excluded) { if (source == null) return {}; var target = _objectWithoutPropertiesLoose(source, excluded); var key, i; if (Object.getOwnPropertySymbols) { var sourceSymbolKeys = Object.getOwnPropertySymbols(source); for (i = 0; i < sourceSymbolKeys.length; i++) { key = sourceSymbolKeys[i]; if (excluded.indexOf(key) >= 0) continue; if (!Object.prototype.propertyIsEnumerable.call(source, key)) continue; target[key] = source[key]; } } return target; }

function _objectWithoutPropertiesLoose(source, excluded) { if (source == null) return {}; var target = {}; var sourceKeys = Object.keys(source); var key, i; for (i = 0; i < sourceKeys.length; i++) { key = sourceKeys[i]; if (excluded.indexOf(key) >= 0) continue; target[key] = source[key]; } return target; }

function CrossSmall(_ref) {
  var _ref$scale = _ref.scale,
      scale = _ref$scale === void 0 ? 'M' : _ref$scale,
      props = _objectWithoutProperties(_ref, ["scale"]);

  return _react["default"].createElement("svg", _extends({}, props, props), scale === 'L' && _react["default"].createElement("path", {
    d: "M9.317 8.433L5.884 5l3.433-3.433a.625.625 0 1 0-.884-.884L5 4.116 1.567.683a.625.625 0 1 0-.884.884C.83 1.713 2.77 3.657 4.116 5L.683 8.433a.625.625 0 1 0 .884.884L5 5.884l3.433 3.433a.625.625 0 0 0 .884-.884z"
  }), scale === 'M' && _react["default"].createElement("path", {
    d: "M7.317 6.433L4.884 4l2.433-2.433a.625.625 0 1 0-.884-.884L4 3.116 1.567.683a.625.625 0 1 0-.884.884L3.116 4 .683 6.433a.625.625 0 1 0 .884.884L4 4.884l2.433 2.433a.625.625 0 0 0 .884-.884z"
  }));
}

CrossSmall.displayName = 'CrossSmall';
