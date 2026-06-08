"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = ColorWheel;
var _ColorWheel = require("@adobe/react-spectrum-workflow/dist/ColorWheel.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function ColorWheel(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_ColorWheel.A4uColorWheel, null));
}