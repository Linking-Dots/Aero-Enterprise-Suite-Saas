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
export function ChevronRightLarge({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M15 10.004a1.243 1.243 0 0 0-.38-.894L5.631.364a1.249 1.249 0 1 0-1.741 1.79l8.066 7.85-8.069 7.847a1.249 1.249 0 0 0 1.741 1.79l8.992-8.74a1.246 1.246 0 0 0 .379-.897z" />
      )}
      {scale === 'M' && (
        <path d="M11.5 8a1.241 1.241 0 0 0-.386-.897L4.128.36a1.248 1.248 0 1 0-1.733 1.797L8.45 8l-6.058 5.84a1.248 1.248 0 1 0 1.733 1.797L11.117 8.9A1.245 1.245 0 0 0 11.5 8z" />
      )}
    </svg>
  );
}
ChevronRightLarge.displayName = 'ChevronRightLarge';
