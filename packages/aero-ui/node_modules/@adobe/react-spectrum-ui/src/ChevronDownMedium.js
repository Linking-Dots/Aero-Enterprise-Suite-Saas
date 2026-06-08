/*
Copyright 2024 Adobe. All rights reserved.
This file is licensed to you under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License. You may obtain a copy
of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under
the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR REPRESENTATIONS
OF ANY KIND, either express or implied. See the License for the specific language
governing permissions and limitations under the License.
*/
import React from 'react';
export function ChevronDownMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M11.99 1.51a1 1 0 0 0-1.707-.707L6 5.086 1.717.803A1 1 0 1 0 .303 2.217l4.99 4.99a1 1 0 0 0 1.414 0l4.99-4.99a.997.997 0 0 0 .293-.707z" />
      )}
      {scale === 'M' && (
        <path d="M9.99 1.01A1 1 0 0 0 8.283.303L5 3.586 1.717.303A1 1 0 1 0 .303 1.717l3.99 3.98a1 1 0 0 0 1.414 0l3.99-3.98a.997.997 0 0 0 .293-.707z" />
      )}
    </svg>
  );
}
ChevronDownMedium.displayName = 'ChevronDownMedium';
