<?php

namespace Aero\Core\Http\Controllers;

use Aero\Kernel\Http\Controllers\Controller as KernelController;

/**
 * Backward-compatibility shim for the base Aero controller.
 *
 * The canonical base now lives in aero-kernel ({@see KernelController}) so that core
 * (tenant/standalone), platform (central), and the shared packages can extend one base
 * without any sibling depending on another. This thin subclass keeps the historical
 * `Aero\Core\Http\Controllers\Controller` FQN resolving for the ~60 existing core
 * controllers (and pure-unit tests that reflect on them) with zero edits. It carries no
 * logic of its own and is removed in the final enforcement phase once all controllers
 * import the kernel FQN directly.
 */
class Controller extends KernelController
{
}
