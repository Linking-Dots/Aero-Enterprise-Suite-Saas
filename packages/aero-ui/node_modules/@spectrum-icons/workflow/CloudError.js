"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = CloudError;
var _CloudError = require("@adobe/react-spectrum-workflow/dist/CloudError.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function CloudError(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_CloudError.A4uCloudError, null));
}