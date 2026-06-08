"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = Spin;
var _Spin = require("@adobe/react-spectrum-workflow/dist/Spin.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function Spin(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_Spin.A4uSpin, null));
}