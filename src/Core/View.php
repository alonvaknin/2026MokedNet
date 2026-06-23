<?php
declare(strict_types=1);

namespace Core;

class View
{
    private string  $template;
    private array   $data   = [];
    private ?string $layout = 'layouts/main';

    public function __construct(string $template, array $data = [])
    {
        $this->template = self::safePath($template);
        $this->data     = $data;
    }

    public function withLayout(?string $layout): static
    {
        $this->layout = $layout !== null ? self::safePath($layout) : null;
        return $this;
    }

    /** Strip path-traversal sequences so template names can never escape the views/ directory. */
    private static function safePath(string $path): string
    {
        // Remove any ../ or ..\ segments and null bytes
        $path = str_replace("\0", '', $path);
        $parts = preg_split('#[/\\\\]#', $path);
        $safe  = array_filter($parts, fn($p) => $p !== '..' && $p !== '.');
        return implode('/', $safe);
    }

    public function render(): void
    {
        $__data = $this->data;
        extract($__data, EXTR_OVERWRITE);

        ob_start();
        $__tplFile = ROOT . '/views/' . $this->template . '.php';
        if (!file_exists($__tplFile)) {
            ob_end_clean();
            throw new \RuntimeException("View not found: {$this->template}");
        }
        require $__tplFile;
        $content = ob_get_clean();

        if ($this->layout) {
            $__layoutFile = ROOT . '/views/' . $this->layout . '.php';
            if (!file_exists($__layoutFile)) {
                throw new \RuntimeException("Layout not found: {$this->layout}");
            }
            require $__layoutFile;
        } else {
            echo $content;
        }
    }

    public static function e(mixed $val): string
    {
        return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function component(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = ROOT . '/views/components/' . self::safePath($name) . '.php';
        if (file_exists($file)) require $file;
    }
}
