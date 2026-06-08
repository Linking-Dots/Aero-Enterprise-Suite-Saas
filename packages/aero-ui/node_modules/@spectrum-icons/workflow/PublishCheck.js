"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = PublishCheck;
var _PublishCheck = require("@adobe/react-spectrum-workflow/dist/PublishCheck.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function PublishCheck(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_PublishCheck.A4uPublishCheck, null));
}