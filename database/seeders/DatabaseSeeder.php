<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        //make permission

        //untuk membuka dashboard
        Permission::create(['name' => 'admin-read']);
        Permission::create(['name' => 'admin-write']);
        //untuk membuka dashboard admin cms
        //make role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo('admin-read');
        $adminRole->givePermissionTo('admin-write');

        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin']);

        $user = User::firstOrCreate(['username' => 'admin',], [
            'name' => 'Example admin user',
            'email' => 'admin@sikeu.com',
            'password' => bcrypt('admkeu90')
        ]);

        $user->assignRole($adminRole);

        $user = User::firstOrCreate(['username' => 'super_admin',], [
            'name' => 'Example admin user',
            'email' => 'super_admin@sikeu.com',
            'password' => bcrypt('admkeu90')
        ]);

        $user->assignRole($superAdminRole);
    }
}
