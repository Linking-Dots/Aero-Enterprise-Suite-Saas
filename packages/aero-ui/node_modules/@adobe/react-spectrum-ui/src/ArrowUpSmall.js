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
export function ArrowUpSmall({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M9.99 4.99a1 1 0 0 1-1.707.707L6 3.414v7.576a1 1 0 0 1-2 0V3.414L1.717 5.697A1 1 0 1 1 .303 4.283l3.99-3.98a1 1 0 0 1 1.414 0l3.99 3.98a.997.997 0 0 1 .293.707z" />
      )}
      {scale === 'M' && (
        <path d="M7.99 3.99a1 1 0 0 1-1.707.707L5 3.414V9a1 1 0 0 1-2 0V3.414L1.717 4.697A1 1 0 1 1 .303 3.283l2.99-2.98a1 1 0 0 1 1.414 0l2.99 2.98a.997.997 0 0 1 .293.707z" />
      )}
    </svg>
  );
}
ArrowUpSmall.displayName = 'ArrowUpSmall';
