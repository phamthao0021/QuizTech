<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    private static array $globals = [];

    public static function setGlobal(string $key, $value): void
    {
        self::$globals[$key] = $value;
    }

    public static function render(string $view, array $data = []): void
    {
        $data = array_merge(self::$globals, $data);
        
        // Extract data to variables
        extract($data);

        $viewPath = __DIR__ . '/../../views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View $view not found");
        }

        // Start output buffering
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // If layout exists, render with layout
        if (isset($layout) && $layout !== false) {
            $layoutPath = __DIR__ . '/../../views/layout/' . $layout . '.php';
            if (file_exists($layoutPath)) {
                require $layoutPath;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    public static function renderPartial(string $view, array $data = []): string
    {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            return '';
        }

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    public static function json(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}