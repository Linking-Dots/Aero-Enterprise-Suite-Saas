import _extends from "@babel/runtime/helpers/extends";
import { AlertMedium as IconComponent } from '@adobe/react-spectrum-ui/dist/AlertMedium.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 18 18"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M9 10.5a1 1 0 0 1-1-1V5a1 1 0 1 1 2 0v4.5a1 1 0 0 1-1 1m0 1.25A1.25 1.25 0 1 0 10.25 13 1.25 1.25 0 0 0 9 11.75m8.497 3.589a3.49 3.49 0 0 0 .079-3.474L12 1.815a3.385 3.385 0 0 0-5.994-.007L.416 11.88a3.49 3.49 0 0 0 .089 3.459A3.38 3.38 0 0 0 3.416 17h11.169a3.38 3.38 0 0 0 2.912-1.661M10.244 2.77l5.575 10.05a1.5 1.5 0 0 1-.037 1.489 1.37 1.37 0 0 1-1.197.69H3.416a1.37 1.37 0 0 1-1.197-.69 1.49 1.49 0 0 1-.046-1.474l5.593-10.08a1.386 1.386 0 0 1 2.478.015"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function AlertMedium(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}