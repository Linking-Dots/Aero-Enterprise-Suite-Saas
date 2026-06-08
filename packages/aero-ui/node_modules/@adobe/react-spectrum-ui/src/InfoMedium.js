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
export function InfoMedium({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M11 2a9 9 0 1 0 9 9 9 9 0 0 0-9-9zm-.15 2.65a1.359 1.359 0 0 1 1.431 1.283q.004.064.001.129a1.332 1.332 0 0 1-1.432 1.432 1.353 1.353 0 0 1-1.432-1.433 1.359 1.359 0 0 1 1.304-1.412q.064-.002.128.001zM13.5 16a.5.5 0 0 1-.5.5H9a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h1v-4H9a.5.5 0 0 1-.5-.5V9a.5.5 0 0 1 .5-.5h2.5a.5.5 0 0 1 .5.5v5.5h1a.5.5 0 0 1 .5.5z" />
      )}
      {scale === 'M' && (
        <path d="M9 1a8 8 0 1 0 8 8 8 8 0 0 0-8-8zm-.15 2.15a1.359 1.359 0 0 1 1.431 1.283q.004.064.001.129A1.332 1.332 0 0 1 8.85 5.994a1.353 1.353 0 0 1-1.432-1.433 1.359 1.359 0 0 1 1.304-1.412q.064-.002.128.001zM11 13.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5H8V9h-.5a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5V12h.5a.5.5 0 0 1 .5.5z" />
      )}
    </svg>
  );
}
InfoMedium.displayName = 'InfoMedium';
