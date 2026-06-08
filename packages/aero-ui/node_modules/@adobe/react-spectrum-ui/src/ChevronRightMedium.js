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
export function ChevronRightMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M7.5 6a.997.997 0 0 0-.293-.707L2.217.303A1 1 0 1 0 .803 1.717L5.086 6 .803 10.283a1 1 0 1 0 1.414 1.414l4.99-4.99A.997.997 0 0 0 7.5 6z" />
      )}
      {scale === 'M' && (
        <path d="M5.99 5a.997.997 0 0 0-.293-.707L1.717.303A1 1 0 1 0 .303 1.717L3.586 5 .303 8.283a1 1 0 1 0 1.414 1.414l3.98-3.99A.997.997 0 0 0 5.99 5z" />
      )}
    </svg>
  );
}
ChevronRightMedium.displayName = 'ChevronRightMedium';
