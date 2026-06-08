"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = InfoMedium;
var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));
var _InfoMedium = require("@adobe/react-spectrum-ui/dist/InfoMedium.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _Provider = require("@adobe/react-spectrum/Provider");
var _react = _interopRequireDefault(require("react"));
const ExpressIcon = props => /*#__PURE__*/_react.default.createElement("svg", (0, _extends2.default)({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/_react.default.createElement("path", {
  d: "M9 8a1 1 0 0 0-1 1v4a1 1 0 0 0 2 0V9a1 1 0 0 0-1-1"
}), /*#__PURE__*/_react.default.createElement("circle", {
  cx: 9,
  cy: 5.5,
  r: 1.5
}), /*#__PURE__*/_react.default.createElement("path", {
  d: "M9 0a9 9 0 1 0 9 9 9.01 9.01 0 0 0-9-9m0 16a7 7 0 1 1 7-7 7.01 7.01 0 0 1-7 7"
}));
ExpressIcon.displayName = _InfoMedium.InfoMedium.displayName;
function InfoMedium(props) {
  let express = false;
  try {
    express = (0, _Provider.useProvider)().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, express ? /*#__PURE__*/_react.default.createElement(ExpressIcon, null) : /*#__PURE__*/_react.default.createElement(_InfoMedium.InfoMedium, null));
}