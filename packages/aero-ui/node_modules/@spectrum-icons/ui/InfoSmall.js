"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = InfoSmall;
var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));
var _InfoSmall = require("@adobe/react-spectrum-ui/dist/InfoSmall.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _Provider = require("@adobe/react-spectrum/Provider");
var _react = _interopRequireDefault(require("react"));
const ExpressIcon = props => /*#__PURE__*/_react.default.createElement("svg", (0, _extends2.default)({
  viewBox: "0 0 14 14"
}, props), /*#__PURE__*/_react.default.createElement("path", {
  d: "M7 1a6 6 0 1 0 6 6 6.007 6.007 0 0 0-6-6m.867 8.677a.868.868 0 0 1-1.734 0V7a.868.868 0 0 1 1.734 0zM7 5.4c-.662 0-1.2-.538-1.2-1.2S6.338 3 7 3s1.2.539 1.2 1.2S7.662 5.4 7 5.4"
}));
ExpressIcon.displayName = _InfoSmall.InfoSmall.displayName;
function InfoSmall(props) {
  let express = false;
  try {
    express = (0, _Provider.useProvider)().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, express ? /*#__PURE__*/_react.default.createElement(ExpressIcon, null) : /*#__PURE__*/_react.default.createElement(_InfoSmall.InfoSmall, null));
}