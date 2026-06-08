"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = AlertSmall;
var _extends2 = _interopRequireDefault(require("@babel/runtime/helpers/extends"));
var _AlertSmall = require("@adobe/react-spectrum-ui/dist/AlertSmall.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _Provider = require("@adobe/react-spectrum/Provider");
var _react = _interopRequireDefault(require("react"));
const ExpressIcon = props => /*#__PURE__*/_react.default.createElement("svg", (0, _extends2.default)({
  viewBox: "0 0 14 14"
}, props), /*#__PURE__*/_react.default.createElement("path", {
  d: "M12.717 8.678 9 2.175C8.422 1.103 7.058.689 5.954 1.25a2.23 2.23 0 0 0-.95.92L1.278 8.687a2.2 2.2 0 0 0 .058 2.238A2.26 2.26 0 0 0 3.278 12h7.445a2.26 2.26 0 0 0 1.941-1.075 2.2 2.2 0 0 0 .053-2.247M6.133 4.133c0-.478.388-.866.866-.867.478 0 .867.388.868.866v3a.868.868 0 0 1-1.734.002zM7 11.1c-.661 0-1.2-.538-1.2-1.2S6.338 8.7 7 8.7s1.2.538 1.2 1.2-.538 1.2-1.2 1.2"
}));
ExpressIcon.displayName = _AlertSmall.AlertSmall.displayName;
function AlertSmall(props) {
  let express = false;
  try {
    express = (0, _Provider.useProvider)().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, express ? /*#__PURE__*/_react.default.createElement(ExpressIcon, null) : /*#__PURE__*/_react.default.createElement(_AlertSmall.AlertSmall, null));
}