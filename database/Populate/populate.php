<?php

require __DIR__ . '/../../config/bootstrap.php';

use Core\Database\Database;
use Database\Populate\DecksPopulate;
use Database\Populate\UserPopulate;

Database::migrate();
UserPopulate::populate();
DecksPopulate::populate();
