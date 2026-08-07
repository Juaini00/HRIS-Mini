<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Entry point for the demo dataset.
 *
 * Order matters: permissions before people (so role sync finds the roles), master data
 * before people (so employees have somewhere to belong). Every seeder in the chain is
 * idempotent, so `db:seed` can be re-run without duplicating rows.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OrganizationSeeder::class,
            PeopleSeeder::class,
            TransactionalDataSeeder::class,
            PayrollDemoSeeder::class,
        ]);
    }
}
