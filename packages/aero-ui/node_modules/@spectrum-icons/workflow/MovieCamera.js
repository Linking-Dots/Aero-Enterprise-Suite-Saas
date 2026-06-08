"use strict";

var _interopRequireDefault = require("@babel/runtime/helpers/interopRequireDefault");
exports.__esModule = true;
exports.default = MovieCamera;
var _MovieCamera = require("@adobe/react-spectrum-workflow/dist/MovieCamera.js");
var _Icon = require("@adobe/react-spectrum/Icon");
var _react = _interopRequireDefault(require("react"));
function MovieCamera(props) {
  return /*#__PURE__*/_react.default.createElement(_Icon.Icon, props, /*#__PURE__*/_react.default.createElement(_MovieCamera.A4uMovieCamera, null));
}