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
export function DoubleGripper({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <>
          <path d="M19.49 4H.51a.5.5 0 1 0 0 1h18.98a.5.5 0 0 0 0-1z" />
          <path d="M.51 1h18.98a.5.5 0 0 0 0-1H.51a.5.5 0 0 0 0 1z" />
        </>
      )}
      {scale === 'M' && (
        <>
          <path d="M15.49 3H.51a.5.5 0 1 0 0 1h14.98a.5.5 0 1 0 0-1z" />
          <path d="M.51 1h14.98a.5.5 0 0 0 0-1H.51a.5.5 0 0 0 0 1z" />
        </>
      )}
    </svg>
  );
}
DoubleGripper.displayName = 'DoubleGripper';
