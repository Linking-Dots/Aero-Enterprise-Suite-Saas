<?php

namespace Aero\Core\PHPStan;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Forbids direct tenancy() calls outside of the stancl/tenancy package.
 *
 * Use TenantScopeInterface instead:
 *   app(TenantScopeInterface::class)->getCurrentTenantId()
 */
class ForbidDirectTenancyCallRule implements Rule
{
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @param FuncCall $node */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Name) {
            return [];
        }

        if ($node->name->toString() !== 'tenancy') {
            return [];
        }

        // Allow inside stancl/tenancy package itself
        $file = $scope->getFile();
        if (str_contains($file, 'stancl') || str_contains($file, 'stancl\\tenancy')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                'Direct tenancy() call is forbidden. ' .
                'Use app(\\Aero\\Core\\Contracts\\TenantScopeInterface::class) instead. ' .
                'tenancy() causes fatal errors in standalone mode.'
            )->build(),
        ];
    }
}
