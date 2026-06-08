"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = SuccessSmall;
var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));
var _SuccessSmall = require("@adobe/react-spectrum-ui/dist/SuccessSmall.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _Provider = require("@adobe/react-spectrum/Provider");
var _react = _interopRequireDefault(require("react"));
const ExpressIcon = props => /*#__PURE__*/_react.default.createElement("svg", (0, _extends2.default)({
  viewBox: "0 0 14 14"
}, props), /*#__PURE__*/_react.default.createElement("path", {
  d: "M11.523 3.057a6 6 0 1 0-9.046 7.887 6 6 0 0 0 9.046-7.887m-1.166 2.275L7.075 9.696a.87.87 0 0 1-.617.342l-.076.004a.86.86 0 0 1-.592-.235L3.55 7.704c-.17-.159-.266-.373-.274-.604s.077-.453.235-.621a.87.87 0 0 1 1.225-.04L6.27 7.881 8.973 4.29a.86.86 0 0 1 .693-.346.867.867 0 0 1 .692 1.388"
}));
ExpressIcon.displayName = _SuccessSmall.SuccessSmall.displayName;
function SuccessSmall(props) {
  let express = false;
  try {
    express = (0, _Provider.useProvider)().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, express ? /*#__PURE__*/_react.default.createElement(ExpressIcon, null) : /*#__PURE__*/_react.default.createElement(_SuccessSmall.SuccessSmall, null));
}