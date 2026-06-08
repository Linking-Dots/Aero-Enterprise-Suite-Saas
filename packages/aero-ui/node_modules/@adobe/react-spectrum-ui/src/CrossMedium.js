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
export function CrossMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M9.77 8.71L6.06 5l3.71-3.71A.75.75 0 1 0 8.71.23L5 3.94 1.29.23A.75.75 0 1 0 .23 1.29L3.94 5 .23 8.71a.75.75 0 1 0 1.06 1.06L5 6.06l3.71 3.71a.75.75 0 1 0 1.06-1.06z" />
      )}
      {scale === 'M' && (
        <path d="M7.77 6.71L5.06 4l2.71-2.71A.75.75 0 1 0 6.71.23L4 2.94 1.29.23A.75.75 0 1 0 .23 1.29L2.94 4 .23 6.71a.75.75 0 1 0 1.06 1.06L4 5.06l2.71 2.71a.75.75 0 1 0 1.06-1.06z" />
      )}
    </svg>
  );
}
CrossMedium.displayName = 'CrossMedium';
