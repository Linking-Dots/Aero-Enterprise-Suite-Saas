"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = FolderBreadcrumb;
var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));
var _FolderBreadcrumb = require("@adobe/react-spectrum-ui/dist/FolderBreadcrumb.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _Provider = require("@adobe/react-spectrum/Provider");
var _react = _interopRequireDefault(require("react"));
const ExpressIcon = props => /*#__PURE__*/_react.default.createElement("svg", (0, _extends2.default)({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/_react.default.createElement("circle", {
  cx: 9,
  cy: 9,
  r: 2
}), /*#__PURE__*/_react.default.createElement("circle", {
  cx: 15,
  cy: 9,
  r: 2
}), /*#__PURE__*/_react.default.createElement("circle", {
  cx: 3,
  cy: 9,
  r: 2
}));
ExpressIcon.displayName = _FolderBreadcrumb.FolderBreadcrumb.displayName;
function FolderBreadcrumb(props) {
  let express = false;
  try {
    express = (0, _Provider.useProvider)().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, express ? /*#__PURE__*/_react.default.createElement(ExpressIcon, null) : /*#__PURE__*/_react.default.createElement(_FolderBreadcrumb.FolderBreadcrumb, null));
}