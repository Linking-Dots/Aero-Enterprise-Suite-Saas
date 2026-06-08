import _extends from "@babel/runtime/helpers/extends";
import { InfoSmall as IconComponent } from '@adobe/react-spectrum-ui/dist/InfoSmall.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 14 14"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M7 1a6 6 0 1 0 6 6 6.007 6.007 0 0 0-6-6m.867 8.677a.868.868 0 0 1-1.734 0V7a.868.868 0 0 1 1.734 0zM7 5.4c-.662 0-1.2-.538-1.2-1.2S6.338 3 7 3s1.2.539 1.2 1.2S7.662 5.4 7 5.4"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function InfoSmall(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}