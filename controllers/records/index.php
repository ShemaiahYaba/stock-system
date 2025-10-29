<?php
// Central export for all CRUD operations

require_once __DIR__ . '/CRUD/create.php';
require_once __DIR__ . '/CRUD/read.php';
require_once __DIR__ . '/CRUD/update.php';
require_once __DIR__ . '/CRUD/delete.php';

// All CRUD functions are now available when this file is included
?>