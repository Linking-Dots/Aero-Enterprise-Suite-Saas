"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = ChevronRightSmall;
var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));
var _ChevronRightSmall = require("@adobe/react-spectrum-ui/dist/ChevronRightSmall.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _Provider = require("@adobe/react-spectrum/Provider");
var _react = _interopRequireDefault(require("react"));
const ExpressIcon = props => /*#__PURE__*/_react.default.createElement("svg", (0, _extends2.default)({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/_react.default.createElement("path", {
  d: "M7.707 4.293a1 1 0 1 0-1.414 1.414L9.586 9l-3.293 3.293a1 1 0 1 0 1.414 1.414l4-4a1 1 0 0 0 0-1.414z"
}));
ExpressIcon.displayName = _ChevronRightSmall.ChevronRightSmall.displayName;
function ChevronRightSmall(props) {
  let express = false;
  try {
    express = (0, _Provider.useProvider)().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, express ? /*#__PURE__*/_react.default.createElement(ExpressIcon, null) : /*#__PURE__*/_react.default.createElement(_ChevronRightSmall.ChevronRightSmall, null));
}