<?php

foreach (File::allFiles(__DIR__ . '/api') as $route_file) {
    require $route_file->getPathname();
}
