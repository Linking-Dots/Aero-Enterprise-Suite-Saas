import _extends from "@babel/runtime/helpers/extends";
import { AlertSmall as IconComponent } from '@adobe/react-spectrum-ui/dist/AlertSmall.js';
import { UIIcon } from '@adobe/react-spectrum/private/icon/UIIcon';
import { useProvider } from '@adobe/react-spectrum/Provider';
import React from 'react';
const ExpressIcon = props => /*#__PURE__*/React.createElement("svg", _extends({
  viewBox: "0 0 14 14"
}, props), /*#__PURE__*/React.createElement("path", {
  d: "M12.717 8.678 9 2.175C8.422 1.103 7.058.689 5.954 1.25a2.23 2.23 0 0 0-.95.92L1.278 8.687a2.2 2.2 0 0 0 .058 2.238A2.26 2.26 0 0 0 3.278 12h7.445a2.26 2.26 0 0 0 1.941-1.075 2.2 2.2 0 0 0 .053-2.247M6.133 4.133c0-.478.388-.866.866-.867.478 0 .867.388.868.866v3a.868.868 0 0 1-1.734.002zM7 11.1c-.661 0-1.2-.538-1.2-1.2S6.338 8.7 7 8.7s1.2.538 1.2 1.2-.538 1.2-1.2 1.2"
}));
ExpressIcon.displayName = IconComponent.displayName;
export default function AlertSmall(props) {
  let express = false;
  try {
    express = useProvider().theme.global.express;
  } catch {
    // ignore
  }
  return /*#__PURE__*/React.createElement(UIIcon, props, express ? /*#__PURE__*/React.createElement(ExpressIcon, null) : /*#__PURE__*/React.createElement(IconComponent, null));
}