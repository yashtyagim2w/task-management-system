<?php

namespace App\Core;

abstract class Controller {

    // SSR Rendering
    protected function render(string $view, array $data = []){
        $viewPath = VIEWS_PATH . $view . ".php";
        $headerComponentPath = COMPONENTS_PATH . '/header.php';
        $sidebarComponentPath = COMPONENTS_PATH . '/sidebar.php';
        $footerComponentPath = COMPONENTS_PATH . '/footer.php';
        $currentURI = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if(!file_exists($viewPath)) {
            exit("View doesn't exist");
        }

        extract($data);
        
        require $headerComponentPath;
        if(!($public_page ?? false)) {
            require $sidebarComponentPath;
            echo sideBar($currentURI);   
        }
        require $viewPath;
        require $footerComponentPath;
    }

    // JSON Response
    protected function json(array $data, int $statusCode = 200): void {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit();
    }

    // Success JSON Response
    protected function success(string $message = '', array $data = [], int $statusCode = 200): void {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];
        $this->json($response, $statusCode);
    }

    // Failure JSON Response
    protected function failure(string $message = '', array $data = [], int $statusCode = 400): void {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => $data,
        ];
        $this->json($response, $statusCode);
    }

    // Get pagination parameters from query string
    protected function getPaginationParams(int $defaultLimit = 10, int $maxLimit = 100): array {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(intval($_GET['limit']), $maxLimit)) : $defaultLimit;
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    // Get sort parameters
    protected function getSortParams(array $allowedColumns, string $defaultColumn = 'created_at'): array {
        $sortBy = $_GET['sort_by'] ?? $defaultColumn;
        $sortOrder = (($_GET['sort_order'] ?? 'DESC') === 'ASC') ? 'ASC' : 'DESC';

        if (!in_array($sortBy, $allowedColumns, true)) {
            $sortBy = $defaultColumn;
        }

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];
    }

    // Build pagination response
    protected function paginatedResponse(array $data, int $page, int $limit, int $totalRows): array {
        $totalPages = $totalRows > 0 ? (int) ceil($totalRows / $limit) : 1;
        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'totalRows' => $totalRows,
                'totalPages' => $totalPages,
            ],
        ];
    }

}