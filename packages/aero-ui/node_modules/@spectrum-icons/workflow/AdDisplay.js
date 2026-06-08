"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = AdDisplay;
var _AdDisplay = require("@adobe/react-spectrum-workflow/dist/AdDisplay.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function AdDisplay(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_AdDisplay.A4uAdDisplay, null));
}