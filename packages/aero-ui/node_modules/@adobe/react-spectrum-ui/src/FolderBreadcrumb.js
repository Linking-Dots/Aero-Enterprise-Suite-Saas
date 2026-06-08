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
export function FolderBreadcrumb({scale = 'M', ...props}) {
  return (
    <svg {...props}>
      {scale === 'L' && (
        <path d="M19.5 6l-9.166.004-1.668-1.7A.998.998 0 0 0 7.946 4H3a1 1 0 0 0-1 1v13a.5.5 0 0 0 .5.5h17a.5.5 0 0 0 .5-.5V6.5a.5.5 0 0 0-.5-.5zm-16-.5h4.237l1.964 2H3.5zm11.544 6.044l-3.5 3.5a.77.77 0 0 1-1.088 0l-3.5-3.5a.77.77 0 0 1 1.088-1.088L11 13.41l2.956-2.955a.77.77 0 0 1 1.088 1.088z" />
      )}
      {scale === 'M' && (
        <path d="M16.5 4l-7.166.004-1.652-1.7A1 1 0 0 0 6.965 2H2a1 1 0 0 0-1 1v11.5a.5.5 0 0 0 .5.5h15a.5.5 0 0 0 .5-.5v-10a.5.5 0 0 0-.5-.5zM2 3h4.965l1.943 2H2zm10.354 5.854l-3 3a.5.5 0 0 1-.707 0l-3-3a.5.5 0 0 1 .707-.707L9 10.793l2.646-2.646a.5.5 0 0 1 .707.707z" />
      )}
    </svg>
  );
}
FolderBreadcrumb.displayName = 'FolderBreadcrumb';
