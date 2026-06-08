"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = Magnifier;
var _Magnifier = require("@adobe/react-spectrum-ui/dist/Magnifier.js");
var _UIIcon = require("@adobe/react-spectrum/private/icon/UIIcon");
var _react = _interopRequireDefault(require("react"));
function Magnifier(props) {
  return /*#__PURE__*/_react.default.createElement(_UIIcon.UIIcon, props, /*#__PURE__*/_react.default.createElement(_Magnifier.Magnifier, null));
}