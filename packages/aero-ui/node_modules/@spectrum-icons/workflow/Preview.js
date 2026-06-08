"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = Preview;
var _Preview = require("@adobe/react-spectrum-workflow/dist/Preview.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function Preview(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_Preview.A4uPreview, null));
}