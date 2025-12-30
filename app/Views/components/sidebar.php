<?php

use App\Helpers\Session;

require_once COMPONENTS_PATH . "/card.php";

function sideBar(string $currentURI)
{
    $admin_sidebar_items = [
        [
            'title' => 'Dashboard',
            'href' => '/admin/dashboard',
        ],
        [
            'title' => 'Manage Users',
            'href' => '/admin/users',
        ],
        [
            'title' => 'Team Assignments',
            'href' => '/admin/team-assignments',
        ],
        [
            'title' => 'View Teams',
            'href' => '/admin/view-team',
        ],
        [
            'title' => 'Projects',
            'href' => '/admin/projects',
        ],
        [
            'title' => 'Tasks',
            'href' => '/admin/tasks',
        ],
    ];

    $manager_sidebar_items = [
        [
            'title' => 'Dashboard',
            'href' => '/manager/dashboard',
        ],
        [
            'title' => 'My Team',
            'href' => '/manager/team',
        ],
        [
            'title' => 'My Projects',
            'href' => '/manager/projects',
        ],
        [
            'title' => 'My Tasks',
            'href' => '/manager/tasks',
        ],
    ];

    $employee_sidebar_items = [
        [
            'title' => 'Dashboard',
            'href' => '/employee/dashboard',
        ],
        [
            'title' => 'My Projects',
            'href' => '/employee/projects',
        ],
        [
            'title' => 'My Tasks',
            'href' => '/employee/tasks',
        ],
    ];
?>
    <aside>
        <?php
        $sidebar_items = [];
        if (Session::get('roleName') === 'super_admin') {
            $sidebar_items = $admin_sidebar_items;
        } elseif (Session::get('roleName') === 'manager') {
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
