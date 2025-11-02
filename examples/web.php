<?php

/**
 * Sample web application for testing browser tracing
 */

function getDatabaseConnection()
{
    // Simulate database connection delay
    usleep(50000); // 50ms
    return new stdClass();
}

function fetchUsers($db)
{
    // Simulate slow query
    usleep(150000); // 150ms
    return [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
        ['id' => 3, 'name' => 'Charlie'],
    ];
}

function renderUsersList($users)
{
    // Simulate template rendering
    usleep(30000); // 30ms

    $html = '<html><head><title>Users</title></head><body>';
    $html .= '<h1>User List</h1>';
    $html .= '<ul>';
    foreach ($users as $user) {
        $html .= '<li>' . htmlspecialchars($user['name']) . '</li>';
    }
    $html .= '</ul>';
    $html .= '<hr>';
    $html .= '<p>Trace this page by adding <code>?TRACE=1</code> to the URL</p>';
    $html .= '<p><a href="?TRACE=1">Enable Trace</a> | <a href="?">Disable Trace</a></p>';
    $html .= '</body></html>';

    return $html;
}

// Main request handler
$db = getDatabaseConnection();
$users = fetchUsers($db);
$output = renderUsersList($users);

echo $output;
