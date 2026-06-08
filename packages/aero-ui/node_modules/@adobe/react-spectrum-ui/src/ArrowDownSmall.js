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
export function ArrowDownSmall({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M9.99 7.01a1 1 0 0 0-1.707-.707L6 8.586V1.01a1 1 0 0 0-2 0v7.576L1.717 6.303A1 1 0 1 0 .303 7.717l3.99 3.98a1 1 0 0 0 1.414 0l3.99-3.98a.997.997 0 0 0 .293-.707z" />
      )}
      {scale === 'M' && (
        <path d="M7.99 6.01a1 1 0 0 0-1.707-.707L5 6.586V1a1 1 0 0 0-2 0v5.586L1.717 5.303A1 1 0 1 0 .303 6.717l2.99 2.98a1 1 0 0 0 1.414 0l2.99-2.98a.997.997 0 0 0 .293-.707z" />
      )}
    </svg>
  );
}
ArrowDownSmall.displayName = 'ArrowDownSmall';
