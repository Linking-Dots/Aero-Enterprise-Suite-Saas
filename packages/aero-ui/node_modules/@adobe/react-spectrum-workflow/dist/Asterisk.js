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
exports.A4uAsterisk = A4uAsterisk;

var _react = _interopRequireDefault(require("react"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _extends() { _extends = Object.assign ? Object.assign.bind() : function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; }; return _extends.apply(this, arguments); }

function A4uAsterisk(_ref) {
  var props = _extends({}, _ref);

  return /*#__PURE__*/_react["default"].createElement("svg", _extends({
    viewBox: "0 0 36 36"
  }, props, props), /*#__PURE__*/_react["default"].createElement("path", {
    fillRule: "evenodd",
    d: "M29.585,29.5c.249.25.417.584,0,.917l-5.167,3.334c-.417.25-.583.083-.751-.334L17.251,22.248,8.833,31.5c-.083.166-.333.332-.582,0l-4-4.168c-.417-.25-.334-.5,0-.749l9.5-7.918L2.917,14.58c-.168,0-.417-.332-.251-.749L5.5,8.164A.438.438,0,0,1,6.25,8l9.5,6.167L16.335,2a.439.439,0,0,1,.5-.5l6.917.916c.417,0,.5.167.417.584L20.918,14.914l11-3.333c.249-.167.5-.167.666.333l1.084,6.167c.083.416,0,.583-.334.583l-11.5.917Z"
  }));
}
