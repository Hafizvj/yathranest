<?php
// HostMaria blocks /admin/media/*.php (403). Use admin/library-api.php instead.
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
redirect('admin/library-api.php');
