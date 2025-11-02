<?php

/**
 * Sample PHP script to test the tracer
 */

function slowDatabaseQuery()
{
    // Simulate a slow database query
    usleep(150000); // 150ms
    return ['id' => 1, 'name' => 'John Doe'];
}

function processUser($userId)
{
    $user = slowDatabaseQuery();

    // Simulate some processing
    usleep(50000); // 50ms

    return validateUser($user);
}

function validateUser($user)
{
    // Simulate validation
    usleep(20000); // 20ms
    return !empty($user['name']);
}

function renderView($data)
{
    // Simulate view rendering
    usleep(30000); // 30ms
    return '<html><body>' . json_encode($data) . '</body></html>';
}

function main()
{
    echo "Starting sample application...\n";

    $userId = 123;
    $isValid = processUser($userId);

    if ($isValid) {
        $html = renderView(['user_id' => $userId, 'valid' => true]);
        echo "User validated successfully!\n";
    } else {
        echo "User validation failed!\n";
    }

    echo "Done!\n";
}

// Run the application
main();
