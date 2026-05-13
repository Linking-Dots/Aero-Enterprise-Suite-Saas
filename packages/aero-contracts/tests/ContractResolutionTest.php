<?php

namespace Aero\Contracts\Tests;

use PHPUnit\Framework\TestCase;

class ContractResolutionTest extends TestCase
{
    /**
     * @dataProvider contractProvider
     */
    public function test_contract_is_a_valid_interface_or_enum(string $fqcn): void
    {
        $this->assertTrue(
            interface_exists($fqcn) || enum_exists($fqcn),
            "Expected {$fqcn} to be an interface or enum, but it does not exist."
        );
    }

    public static function contractProvider(): array
    {
        return [
            ['Aero\Contracts\TenantScopeInterface'],
            ['Aero\Contracts\LicenseServiceInterface'],
            ['Aero\Contracts\ProductAccessInterface'],
            ['Aero\Contracts\PlanCatalogInterface'],
            ['Aero\Contracts\RoleModuleAccessInterface'],
            ['Aero\Contracts\UserContract'],
            ['Aero\Contracts\EmployeeServiceContract'],
            ['Aero\Contracts\MailSenderInterface'],
            ['Aero\Contracts\SmsGatewayInterface'],
            ['Aero\Contracts\TranslationDriverInterface'],
            ['Aero\Contracts\ModuleSummaryProvider'],
            ['Aero\Contracts\DomainContextContract'],
            ['Aero\Contracts\DomainEventContract'],
            ['Aero\Contracts\Searchable'],
        ];
    }
}
