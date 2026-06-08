"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.ListGripper = ListGripper;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

/*
Copyright 2024 Adobe. All rights reserved.
This file is licensed to you under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License. You may obtain a copy
of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under
the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
OF ANY KIND, either express or implied. See the License for the specific language
governing permissions and limitations under the License.
*/
function ListGripper({
  scale = 'M',
  ...props
}) {
  return /*#__PURE__*/_react.default.createElement("svg", props, scale === 'L' && /*#__PURE__*/_react.default.createElement(_react.default.Fragment, null, /*#__PURE__*/_react.default.createElement("circle", {
    cx: "5.375",
    cy: "12.625",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1.625",
    cy: "12.625",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "5.375",
    cy: "8.875",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1.625",
    cy: "8.875",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "5.375",
    cy: "5.125",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1.625",
    cy: "5.125",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "5.375",
    cy: "1.375",
    r: "1.25"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1.625",
    cy: "1.375",
    r: "1.25"
  })), scale === 'M' && /*#__PURE__*/_react.default.createElement(_react.default.Fragment, null, /*#__PURE__*/_react.default.createElement("circle", {
    cx: "4",
    cy: "10.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1",
    cy: "10.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "4",
    cy: "7.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1",
    cy: "7.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "4",
    cy: "4.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1",
    cy: "4.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "4",
    cy: "1.5",
    r: "1"
  }), /*#__PURE__*/_react.default.createElement("circle", {
    cx: "1",
    cy: "1.5",
    r: "1"
  })));
}

ListGripper.displayName = 'ListGripper';
