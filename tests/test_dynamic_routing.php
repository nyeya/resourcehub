<?php
/**
 * Test Dynamic Routing & Convention-over-Configuration
 * Verifies that new controllers, actions, and templates are discovered automatically
 * with ZERO route registration or manual file changes.
 */

require_once __DIR__ . '/../includes/init.php';

echo "==========================================================\n";
echo "  Dynamic Routing & Autoloading Test Suite               \n";
echo "==========================================================\n\n";

$router = new Router();

// -----------------------------------------------------------
// 1. Test Dynamic URL Generation Helper
// -----------------------------------------------------------
echo "Testing dynamic url() helper...\n";

// Clean Path URL Generation (Zero query parameters for pages)
assert(url('dashboard') === 'dashboard', 'url(dashboard) should resolve to dashboard');
assert(url('equipment') === 'equipment', 'url(equipment) should resolve to equipment');
assert(url('equipment', ['action' => 'create']) === 'equipment/create', 'url(equipment, create) should resolve to equipment/create');
assert(url('loans', ['action' => 'request']) === 'loans/request', 'url(loans, request) should resolve to loans/request');
assert(url('maintenance', ['action' => 'open']) === 'maintenance/open', 'url(maintenance, open) should resolve to maintenance/open');
assert(url('users', ['action' => 'edit', 'id' => 4]) === 'users/4/edit', 'url(users, edit, id=4) should resolve to users/4/edit');

// Dynamic clean paths for future resources
assert(url('category') === 'category', 'url(category) should resolve to category');
assert(url('categories', ['action' => 'create']) === 'categories/create', 'url(categories, create) should resolve to categories/create');
assert(url('audit_logs', ['action' => 'view', 'id' => 42]) === 'audit_logs/42', 'url(audit_logs, view, 42) should resolve to audit_logs/42');
assert(url('equipment', ['p' => 2, 'search' => 'mic']) === 'equipment?p=2&search=mic', 'url query parameters for filters preserved');

echo "  -> url() dynamically generates clean semantic path URLs: PASS\n";

// -----------------------------------------------------------
// 2. Test Dynamic Controller Autoloading and Dispatch
// -----------------------------------------------------------
echo "\nTesting dynamic controller autoloading and dispatch...\n";

// Dynamically create a brand new controller file in src/Controllers/
$testControllerFile = RH_SRC . '/Controllers/SampleFeatureController.php';
$controllerCode = <<<'PHP'
<?php
class SampleFeatureController {
    public static $calledAction = null;
    public static $calledId = null;

    public function index() {
        self::$calledAction = 'index';
        return 'SAMPLE_INDEX_OK';
    }

    public function create() {
        self::$calledAction = 'create';
        return 'SAMPLE_CREATE_OK';
    }

    public function show($id) {
        self::$calledAction = 'show';
        self::$calledId = $id;
        return 'SAMPLE_SHOW_OK';
    }

    public function customAction() {
        self::$calledAction = 'customAction';
        return 'SAMPLE_CUSTOM_OK';
    }
}
PHP;

file_put_contents($testControllerFile, $controllerCode);

try {
    // 2a. Dispatch by ?page=sample_feature
    $_GET = ['page' => 'sample_feature'];
    ob_start();
    $result = $router->dispatch();
    $output = ob_get_clean();
    assert($output === 'SAMPLE_INDEX_OK' || SampleFeatureController::$calledAction === 'index', 'Dynamic index dispatch failed');
    echo "  -> ?page=sample_feature dynamically routed to SampleFeatureController::index(): PASS\n";

    // 2b. Dispatch by ?page=sample_feature&action=create
    $_GET = ['page' => 'sample_feature', 'action' => 'create'];
    ob_start();
    $router->dispatch();
    ob_get_clean();
    assert(SampleFeatureController::$calledAction === 'create', 'Dynamic create dispatch failed');
    echo "  -> ?page=sample_feature&action=create dynamically routed to SampleFeatureController::create(): PASS\n";

    // 2c. Dispatch compound action ?page=sample_feature_add
    $_GET = ['page' => 'sample_feature_add'];
    ob_start();
    $router->dispatch();
    ob_get_clean();
    assert(SampleFeatureController::$calledAction === 'create', 'Compound slug sample_feature_add failed');
    echo "  -> ?page=sample_feature_add dynamically mapped to SampleFeatureController::create(): PASS\n";

    // 2d. Dispatch with ID parameter ?page=sample_feature&action=show&id=99
    $_GET = ['page' => 'sample_feature', 'action' => 'show', 'id' => '99'];
    ob_start();
    $router->dispatch();
    ob_get_clean();
    assert(SampleFeatureController::$calledAction === 'show' && SampleFeatureController::$calledId === 99, 'Dynamic show with ID failed');
    echo "  -> ?page=sample_feature&action=show&id=99 passed ID parameter: PASS\n";

    // 2e. Dispatch clean path route "sample_feature/77"
    $_GET = ['route' => 'sample_feature/77'];
    ob_start();
    $router->dispatch();
    ob_get_clean();
    assert(SampleFeatureController::$calledAction === 'show' && SampleFeatureController::$calledId === 77, 'Clean path sample_feature/77 failed');
    echo "  -> Clean path sample_feature/77 dynamically parsed: PASS\n";

    // 2f. Dispatch camelCase method conversion "custom_action" -> "customAction"
    $_GET = ['page' => 'sample_feature', 'action' => 'custom_action'];
    ob_start();
    $router->dispatch();
    ob_get_clean();
    assert(SampleFeatureController::$calledAction === 'customAction', 'camelCase method conversion failed');
    echo "  -> custom_action action resolved to customAction(): PASS\n";

} finally {
    // Clean up temporary test controller
    if (file_exists($testControllerFile)) {
        unlink($testControllerFile);
    }
}

// -----------------------------------------------------------
// 3. Test Dynamic View Fallback (Presentational Page Without Controller)
// -----------------------------------------------------------
echo "\nTesting dynamic presentational template fallback...\n";

$testTemplateFile = RH_TEMPLATES . '/sample_static_page.php';
$templateContent = 'STATIC_VIEW_RENDERED';
file_put_contents($testTemplateFile, $templateContent);

try {
    $_GET = ['page' => 'sample_static_page'];
    ob_start();
    $router->dispatch();
    $viewOutput = ob_get_clean();
    assert(str_contains($viewOutput, 'STATIC_VIEW_RENDERED'), 'Dynamic template fallback failed');
    echo "  -> Presentational template templates/sample_static_page.php rendered automatically: PASS\n";
} finally {
    if (file_exists($testTemplateFile)) {
        unlink($testTemplateFile);
    }
}

// -----------------------------------------------------------
// 4. Test Optional Programmatic Route Overrides
// -----------------------------------------------------------
echo "\nTesting programmatic Router::addRoute()...\n";

class ProgrammaticTestController {
    public function customAction() {
        return 'PROGRAMMATIC_ROUTE_OK';
    }
}

Router::addRoute('custom/quick-path', 'ProgrammaticTestController', 'customAction');
$_GET = ['route' => 'custom/quick-path'];
ob_start();
$router->dispatch();
$content = ob_get_clean();
assert($content === 'PROGRAMMATIC_ROUTE_OK', 'Router::addRoute() custom alias failed');
echo "  -> Router::addRoute() custom alias dispatched successfully: PASS\n";

echo "\n==========================================================\n";
echo "  ALL DYNAMIC ROUTING & AUTOLOADING TESTS PASSED (100%)    \n";
echo "==========================================================\n";
