<?php

use App\Helpers\Session;

require_once COMPONENTS_PATH . "/card.php";

function sideBar(string $currentURI) {
    $admin_sidebar_items = [
        [
            'title' => 'Dashboard',
            'href' => '/admin/dashboard',
        ],
        [
            'title' => 'Manage users',
            'href' => '/admin/users',
        ],
        [
            'title' => 'Manage projects',
            'href' => '/admin/projects',
        ],
        [
            'title' => 'Reports',
            'href' => '/admin/reports',
        ],
    ];

    $manager_sidebar_items = [
        [
            'title' => 'Dashboard',
            'href' => '/manager/dashboard',
        ],
        [
            'title' => 'Manage team',
            'href' => '/manager/vehicles',
        ],
    ];

    $employee_sidebar_items = [
        [
            'title' => 'Dashboard',
            'href' => '/employee/dashboard',
        ],
        [
            'title' => 'Projects',
            'href' => '/employee/projects',
        ],
    ];
    ?>
    <aside>
        <?php
        $sidebar_items = [];
        if(Session::get('roleName') === 'super_admin') {
            $sidebar_items = $admin_sidebar_items;
        } elseif(Session::get('roleName') === 'manager') {
            $sidebar_items = $manager_sidebar_items;
        } else {
            $sidebar_items = $employee_sidebar_items;
        }
    
        foreach ($sidebar_items as $item) {
            $title = $item['title'];
            $href = $item['href'];
            $isActive = $item['href'] === $currentURI;

            echo card($title, $href, $isActive);
        }
        ?>
    </aside>

    <?php return ob_get_clean();
}