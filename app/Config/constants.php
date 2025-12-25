<?php

define('BASE_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', dirname(__DIR__));
define('VIEWS_PATH', APP_PATH . '/Views');
define('COMPONENTS_PATH', VIEWS_PATH . '/components');

// status codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_NO_CONTENT', 204);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_INTERNAL_SERVER_ERROR', 500);  