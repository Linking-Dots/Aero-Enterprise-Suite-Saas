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
export function More({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M11 8.95A2.05 2.05 0 1 1 8.95 11 2.05 2.05 0 0 1 11 8.95zm6 0A2.05 2.05 0 1 1 14.95 11 2.05 2.05 0 0 1 17 8.95zm-12 0A2.05 2.05 0 1 1 2.95 11 2.05 2.05 0 0 1 5 8.95z" />
      )}
      {scale === 'M' && (
        <path d="M9 7.1A1.9 1.9 0 1 1 7.1 9 1.9 1.9 0 0 1 9 7.1zm6 0A1.9 1.9 0 1 1 13.1 9 1.9 1.9 0 0 1 15 7.1zm-12 0A1.9 1.9 0 1 1 1.1 9 1.9 1.9 0 0 1 3 7.1z" />
      )}
    </svg>
  );
}
More.displayName = 'More';
