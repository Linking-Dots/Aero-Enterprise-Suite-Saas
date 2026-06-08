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
export function CrossLarge({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M15.697 14.283L9.414 8l6.283-6.283A1 1 0 1 0 14.283.303L8 6.586 1.717.303A1 1 0 1 0 .303 1.717L6.586 8 .303 14.283a1 1 0 1 0 1.414 1.414L8 9.414l6.283 6.283a1 1 0 1 0 1.414-1.414z" />
      )}
      {scale === 'M' && (
        <path d="M11.697 10.283L7.414 6l4.283-4.283A1 1 0 1 0 10.283.303L6 4.586 1.717.303A1 1 0 1 0 .303 1.717L4.586 6 .303 10.283a1 1 0 1 0 1.414 1.414L6 7.414l4.283 4.283a1 1 0 1 0 1.414-1.414z" />
      )}
    </svg>
  );
}
CrossLarge.displayName = 'CrossLarge';
