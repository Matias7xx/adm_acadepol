<?php

namespace Database\Seeders;

use BalajiDharma\LaravelCategory\Models\CategoryType;
use BalajiDharma\LaravelMenu\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminCoreSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions
        $permissions = [
            'admin user',
            'permission list',
            'permission create',
            'permission edit',
            'permission delete',
            'role list',
            'role create',
            'role edit',
            'role delete',
            'user list',
            'user create',
            'user edit',
            'user delete',
            'menu list',
            'menu create',
            'menu edit',
            'menu delete',
            'menu.item list',
            'menu.item create',
            'menu.item edit',
            'menu.item delete',
            'category list',
            'category create',
            'category edit',
            'category delete',
            'category.type list',
            'category.type create',
            'category.type edit',
            'category.type delete',
            'media list',
            'media create',
            'media edit',
            'media delete',
            'comment list',
            'comment create',
            'comment edit',
            'comment delete',
            'thread list',
            'thread create',
            'thread edit',
            'thread delete',
            'curso list',
            'curso create',
            'curso edit',
            'curso delete',
            'director list',
            'director create',
            'director edit',
            'director delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $role1 = Role::create(['name' => 'super-admin']);
        // gets all permissions via Gate::before rule; see AuthServiceProvider

        $role2 = Role::create(['name' => 'admin']);
        foreach ($permissions as $permission) {
            $role2->givePermissionTo($permission);
        }

        // create roles and assign existing permissions
        $role3 = Role::create(['name' => 'servidor']);
        /* $role3->givePermissionTo('admin user'); */ //Permissão de super usuário para o Servidor

        $role4 = Role::create(['name' => 'aluno']);

        foreach ($permissions as $permission) {
            if (Str::contains($permission, 'list')) {
                $role3->givePermissionTo($permission);
            }
        }

        // create demo users
        $user = \App\Models\User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'matricula' => '0000000'
        ]);
        $user->assignRole($role1);

        $user = \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'matricula' => '0000001'
        ]);
        $user->assignRole($role2);

        $user = \App\Models\User::factory()->create([
            'name' => 'Example User',
            'email' => 'test@example.com',
            'matricula' => '0000002'
        ]);
        $user->assignRole($role3);

        // create menu
        $menu = Menu::create([
            'name' => 'Admin',
            'machine_name' => 'admin',
            'description' => 'Admin Menu',
        ]);

        // create servidores menu
        $servidorMenu = Menu::create([
            'name' => 'Servidor',
            'machine_name' => 'servidor',
            'description' => 'Servidor Menu',
        ]);

        $menu_items = [
            [
                'name' => 'Dashboard',
                'uri' => '/<admin>',
                'enabled' => 1,
                'weight' => 0,
                'icon' => 'M13 9V3H21V9H13M13 21H21V11H13M3 21H11V15H3M3 13H11V3H3V13Z', // Dashboard grid icon
            ],
            [
                'name' => 'Permissões',
                'uri' => '/<admin>/permission',
                'enabled' => 1,
                'weight' => 1,
                'icon' => 'M12 1L3 5V11C3 16.55 6.84 21.74 12 23C17.16 21.74 21 16.55 21 11V5L12 1Z', // Shield with check
            ],
            [
                'name' => 'Funções',
                'uri' => '/<admin>/role',
                'enabled' => 1,
                'weight' => 2,
                'icon' => 'M18 10.5V6L11 1L4 6V18H2V20H22V18H20V12.5L18 10.5M16 10.5C16 11.05 15.55 11.5 15 11.5H9C8.45 11.5 8 11.05 8 10.5V6L11 3.5L14 6V10.5H16Z', // Briefcase
            ],
            [
                'name' => 'Usuários',
                'uri' => '/<admin>/user',
                'enabled' => 1,
                'weight' => 3,
                'icon' => 'M16 17V19H2V17S2 13 9 13 16 17 16 17M12.5 7.5A3.5 3.5 0 1 0 9 11A3.5 3.5 0 0 0 12.5 7.5M15.94 13A5.32 5.32 0 0 1 18 17V19H22V17S22 13.37 15.94 13M15 4A3.39 3.39 0 0 0 13.07 4.59A5 5 0 0 1 13.07 10.41A3.39 3.39 0 0 0 15 11A3.5 3.5 0 0 0 15 4Z', // Multiple users
            ],
            [
                'name' => 'Menus',
                'uri' => '/<admin>/menu',
                'enabled' => 1,
                'weight' => 4,
                'icon' => 'M3 6H21V8H3V6M3 11H21V13H3V11M3 16H21V18H3V16Z', // Hamburger menu lines
            ],
            [
                'name' => 'Categorias',
                'uri' => '/<admin>/category/type',
                'enabled' => 1,
                'weight' => 5,
                'icon' => 'M22 11V3H11V5H5V3H2V11H5V9H8V19H22V11M8 9V5H11V9H8M20 19H10V11H20V19Z', // Layers/stack
            ],
            [
                'name' => 'Mídia',
                'uri' => '/<admin>/media',
                'enabled' => 1,
                'weight' => 6,
                'icon' => 'M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3M19 19H5V5H19V19M13.96 12.29L11.21 15.83L9.25 13.47L6.5 17H17.5L13.96 12.29Z', // Image with photo icon
            ],
            [
                'name' => 'Cursos',
                'uri' => '/<admin>/cursos',
                'enabled' => 1,
                'weight' => 7,
                'icon' => 'M12 3L1 9L12 15L21 10.09V17H23V9M5 13.18V17.18L12 21L19 17.18V13.18L12 17L5 13.18Z', // Graduation cap
            ],
            [
                'name' => 'Diretores',
                'uri' => '/<admin>/directors',
                'enabled' => 1,
                'weight' => 8,
                'icon' => 'M9 11.5A2.5 2.5 0 1 0 6.5 9A2.5 2.5 0 0 0 9 11.5M9 8.5A2.5 2.5 0 1 0 6.5 6A2.5 2.5 0 0 0 9 8.5M9 13.75C6.66 13.75 2 14.92 2 17.25V19H16V17.25C16 14.92 11.34 13.75 9 13.75M14 19H22V17.25C22 15.36 18.66 14.25 17 14.25C16.57 14.25 16 14.43 15.41 14.63A5.26 5.26 0 0 1 16.5 16A5.52 5.52 0 0 1 16.24 17.5H14Z', // User with leader badge
            ],
            [
                'name' => 'Alojamento',
                'uri' => '/<admin>/alojamento',
                'enabled' => 1,
                'weight' => 9,
                'icon' => 'M19 9H17V7H19M19 13H17V11H19M19 17H17V15H19M7 7H5V9H7M7 11H5V13H7M7 15H5V17H7M11 7H9V9H11M11 11H9V13H11M11 15H9V17H11M15 7V17H3V5H21V15H15V7H15M13 5H3V3H21V5H13Z', // Document with margins
            ],
            [
                'name' => 'Notícias',
                'uri' => '/<admin>/noticias',
                'enabled' => 1,
                'weight' => 10,
                'icon' => 'M20 11V8H16V5H12V8H8V5H4V8H2V18H22V11H20M20 16H4V8H6V11H10V8H14V11H18V8H20V16Z', // Newspaper
            ],
            [
                'name' => 'Fale Conosco',
                'uri' => '/<admin>/contato',
                'enabled' => 1,
                'weight' => 11,
                'icon' => 'M22 11V3H11V5H5V3H2V11H5V9H8V19H22V11M8 9V5H11V9H8M20 19H10V11H20V19Z', // Layers/stack
            ],
        ];

        $menu->menuItems()->createMany($menu_items);

        $servidorMenu_items = [
            [
                'name' => 'Meus Cursos',
                'uri' => '/<servidor>',
                'enabled' => 1,
                'weight' => 0,
                'icon' => 'M13,3V9H21V3M13,21H21V11H13M3,21H11V15H3M3,13H11V3H3V13Z',
            ],
        ];

        $servidorMenu->menuItems()->createMany($servidorMenu_items);

        // create category type
        CategoryType::create([
            'name' => 'Categoria',
            'machine_name' => 'category',
            'description' => 'Main Category',
        ]);

        CategoryType::create([
            'name' => 'Tag',
            'machine_name' => 'tag',
            'description' => 'Site Tags',
            'is_flat' => true,
        ]);

        CategoryType::create([
            'name' => 'Admin Tag',
            'machine_name' => 'admin_tag',
            'description' => 'Admin Tags',
            'is_flat' => true,
        ]);

        $forumCategoryType = CategoryType::create([
            'name' => 'Fórum Categoria',
            'machine_name' => 'forum_category',
            'description' => 'Forum Category',
        ]);

        $forumCategoryType->categories()->create([
            'name' => 'Geral',
            'description' => 'Fórum Geral',
        ]);

        CategoryType::create([
            'name' => 'Forum Tag',
            'machine_name' => 'forum_tag',
            'description' => 'Forum Tags',
            'is_flat' => true,
        ]);
    }
}
