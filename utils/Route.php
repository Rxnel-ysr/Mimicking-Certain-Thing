<?php
class Route
{
    private static array $routes = [];

    public static function getRequestMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public static function get(string $url, callable|array $action)
    {
        self::$routes['GET'][$url] = $action;
    }

    public static function post(string $url, callable|array $action)
    {
        self::$routes['POST'][$url] = $action;
    }

    public static function put(string $url, callable|array $action)
    {
        self::$routes['PUT'][$url] = $action;
    }

    public static function delete(string $url, callable|array $action)
    {
        self::$routes['DELETE'][$url] = $action;
    }

    public static function dispatch(string $requestUri)
    {
        /**
         * Serve the request
         * 
         * Note: I've tried to place it some where to make my code looks cleaner but it doesn't work as expected, I'll figured a way later
         * */
        $prohibitedTypes = explode(',', getenv('GET_DENIED'));
        $allowedTypes = explode(',', getenv('GET_ALLOWED'));

        $strictMode = filter_var(getenv('GET_STRICT'), FILTER_VALIDATE_BOOLEAN);

        // error_log(($strictMode) ? 'yeah' : 'nah'); 
        {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $file = ROOT . $path;


            if (file_exists($file) && is_file($file)) {
                $fileType = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $fileName = basename($file);

                $isProhibited = in_array($fileType, $prohibitedTypes);
                $isAllowed = in_array($fileType, $allowedTypes);

                // Strict mode: block anything not explicitly allowed
                if (($isProhibited || (!$isAllowed && $strictMode)) && $fileName !== 'download.php') {
                    Utils::log("WARN - User is a little curious at '{$path}'");

                    $message = match (true) {
                        $fileName === 'error.php' => 'Do you really like to see error page so much?',
                        $fileType === 'php' => 'You can access them normally in a webpage, why here?',
                        default => 'We are sorry but what you have requested is not allowed<br /> <p style="font-size:15px;">Unless if you ask me nicely in person</p>',
                    };

                    Utils::showErrorPage(403, 'Are you curious little fella?', $message, 'Got Curious?');
                    return true;
                }

                return false;
            }
        }
        
        $method = self::getRequestMethod();

        if (!isset(self::$routes[$method])) {
            Utils::showErrorPage(404);
        } else {
            foreach (self::$routes[$method] as $route => $action) {
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route);
                $pattern = "#^" . $pattern . "$#";

                if (preg_match($pattern, $requestUri, $matches)) {
                    array_shift($matches);

                    if (is_array($action) && class_exists($action[0]) && method_exists($action[0], $action[1])) {
                        $controller = new $action[0]();
                        return call_user_func_array([$controller, $action[1]], $matches);
                    }

                    if (is_callable($action)) {
                        return call_user_func_array($action, $matches);
                    }
                }
            }
        }
        Utils::showErrorPage(404);
    }
}
