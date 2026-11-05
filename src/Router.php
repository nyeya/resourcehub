<?php
/**
 * ResourceHub Dynamic Router
 * 
 * Provides convention-over-configuration routing with zero manual registration needed:
 * - Automatically resolves ?page=X or /X to XController -> action()
 * - Automatically resolves compound slugs like equipment_add -> EquipmentController::create()
 * - Automatically resolves new controllers added to src/Controllers/ via autoloader
 * - Automatically resolves presentational templates in templates/ without controller boilerplate
 * - Supports optional programmatic custom route aliases via Router::addRoute()
 */

class Router {
    /**
     * Optional custom routes table: [ 'slug' => ['ControllerClass', 'actionMethod'] ]
     */
    private static $customRoutes = [];

    /**
     * Register a custom route mapping (optional)
     */
    public static function addRoute($path, $controller, $action = 'index') {
        $cleanPath = trim(strtolower($path), '/');
        self::$customRoutes[$cleanPath] = [$controller, $action];
    }

    /**
     * Dispatch the current HTTP request dynamically
     */
    public function dispatch($defaultPage = null) {
        $route = $this->detectRoute($defaultPage);

        // 1. Check explicitly registered custom routes
        if (isset(self::$customRoutes[$route])) {
            [$ctrlClass, $actionMethod] = self::$customRoutes[$route];
            if (class_exists($ctrlClass)) {
                $ctrl = new $ctrlClass();
                $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;
                return $this->invokeMethod($ctrl, $actionMethod, $id);
            }
        }

        // 2. Resolve resource, action, and ID from route
        [$resource, $action, $id] = $this->parseResourceAndAction($route);

        if ($id === null && !empty($_GET['id'])) {
            $id = (int)$_GET['id'];
        }

        // 3. Resolve Controller class
        $controllerClass = $this->findControllerClass($resource, $route);

        if ($controllerClass && class_exists($controllerClass)) {
            $controller = new $controllerClass();
            $method = $this->findActionMethod($controller, $action);

            if ($method) {
                return $this->invokeMethod($controller, $method, $id);
            }
        }

        // 4. Fallback to presentational template if no controller found
        $template = $this->findTemplate($resource, $action, $route);
        if ($template) {
            view($template);
            return true;
        }

        // 5. Route not found -> 404
        http_response_code(404);
        view('errors/404');
        return false;
    }

    /**
     * Detect the requested route from query string, path info, or request URI
     */
    private function detectRoute($defaultPage = null) {
        if (!empty($_GET['page'])) {
            return trim(strtolower($_GET['page']), '/');
        }
        if (!empty($_GET['route'])) {
            return trim(strtolower($_GET['route']), '/');
        }

        if (!empty($_SERVER['PATH_INFO'])) {
            return trim(strtolower($_SERVER['PATH_INFO']), '/');
        }

        // Check rewritten REQUEST_URI if routed through front controller
        if (!empty($_SERVER['REQUEST_URI'])) {
            $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            if ($scriptDir !== '/' && $scriptDir !== '.' && str_starts_with($uriPath, $scriptDir)) {
                $uriPath = substr($uriPath, strlen($scriptDir));
            }
            $uriPath = trim($uriPath, '/');
            if ($uriPath !== '' && $uriPath !== 'index.php' && !str_ends_with($uriPath, '.php')) {
                return strtolower($uriPath);
            }
        }

        if ($defaultPage) {
            return trim(strtolower($defaultPage), '/');
        }

        return Auth::check() ? 'dashboard' : 'login';
    }

    /**
     * Parse resource, action, and ID from route string and query parameters
     */
    private function parseResourceAndAction($route) {
        $id = !empty($_GET['id']) ? (int)$_GET['id'] : null;

        // Special auth endpoints
        if ($route === 'login') {
            return ['auth', 'login', $id];
        }
        if ($route === 'logout') {
            return ['auth', 'logout', $id];
        }

        // If route contains path slashes (e.g. "equipment/5", "equipment/5/edit", "categories/create")
        if (str_contains($route, '/')) {
            $parts = array_values(array_filter(explode('/', $route), 'strlen'));
            $resource = $parts[0] ?? 'dashboard';
            $action = 'index';

            if (isset($parts[1])) {
                if (is_numeric($parts[1])) {
                    $id = (int)$parts[1];
                    $action = $parts[2] ?? 'view';
                } else {
                    $action = $parts[1];
                    if (isset($parts[2]) && is_numeric($parts[2])) {
                        $id = (int)$parts[2];
                    }
                }
            }

            if (!empty($_GET['action'])) {
                $action = strtolower(trim($_GET['action']));
            }

            return [$resource, $action, $id];
        }

        // If explicit action parameter is provided
        if (!empty($_GET['action'])) {
            return [$route, strtolower(trim($_GET['action'])), $id];
        }

        // Detect compound action suffixes (e.g. equipment_add, loan_request, user_edit)
        $suffixes = [
            '_add'     => 'create',
            '_create'  => 'create',
            '_edit'    => 'edit',
            '_view'    => 'view',
            '_show'    => 'view',
            '_detail'  => 'view',
            '_request' => 'request',
            '_open'    => 'open',
            '_resolve' => 'resolve',
        ];

        foreach ($suffixes as $suffix => $mappedAction) {
            if (str_ends_with($route, $suffix)) {
                $baseResource = substr($route, 0, -strlen($suffix));
                return [$baseResource, $mappedAction, $id];
            }
        }

        return [$route, 'index', $id];
    }

    /**
     * Find controller class candidate using naming conventions
     */
    private function findControllerClass($resource, $route) {
        $toPascal = function($str) {
            return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
        };

        $candidates = [
            $toPascal($resource) . 'Controller',
            $toPascal(rtrim($resource, 's')) . 'Controller',
            $toPascal($resource . 's') . 'Controller',
            $toPascal($route) . 'Controller',
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * Find matching action method on controller using naming conventions and aliases
     */
    private function findActionMethod($controller, $action) {
        if (method_exists($controller, $action)) {
            return $action;
        }

        // CamelCase variant (e.g. export_loans -> exportLoans)
        $camelAction = lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $action))));
        if (method_exists($controller, $camelAction)) {
            return $camelAction;
        }

        // Action alias mappings
        $aliasMap = [
            'create'  => ['create', 'add', 'open', 'store'],
            'add'     => ['add', 'create', 'open'],
            'open'    => ['open', 'create', 'add'],
            'edit'    => ['edit', 'update'],
            'view'    => ['view', 'show', 'detail'],
            'show'    => ['view', 'show', 'detail'],
            'request' => ['request', 'create'],
        ];

        if (isset($aliasMap[$action])) {
            foreach ($aliasMap[$action] as $candidate) {
                if (method_exists($controller, $candidate)) {
                    return $candidate;
                }
            }
        }

        // Default fallback method
        if (method_exists($controller, 'index')) {
            return 'index';
        }

        return null;
    }

    /**
     * Invoke controller method with optional ID parameter if supported
     */
    private function invokeMethod($controller, $method, $id = null) {
        $reflection = new ReflectionMethod($controller, $method);
        $numParams = $reflection->getNumberOfParameters();

        if ($numParams > 0 && $id !== null) {
            $result = $controller->$method($id);
        } else {
            $result = $controller->$method();
        }

        if (is_string($result)) {
            echo $result;
        }

        return $result;
    }

    /**
     * Check if a static view template exists matching the resource/action
     */
    private function findTemplate($resource, $action, $route) {
        $templatesDir = defined('RH_TEMPLATES') ? RH_TEMPLATES : dirname(__DIR__) . '/templates';

        $candidates = [
            "{$route}",
            "{$route}/index",
            "{$resource}/{$action}",
            "{$resource}/index",
            "{$resource}",
        ];

        if (in_array($action, ['create', 'add', 'edit'], true)) {
            $candidates[] = "{$resource}/form";
        }

        foreach ($candidates as $c) {
            if (file_exists($templatesDir . '/' . $c . '.php')) {
                return $c;
            }
        }

        return null;
    }
}
