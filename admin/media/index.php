<?php
// HostMaria blocks /admin/media/*.php (403). Use admin/library.php instead.
require dirname(__DIR__) . '/includes/bootstrap.php';
redirect('admin/library.php');
