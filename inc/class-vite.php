<?php
/**
 * Vite Asset Loader
 *
 * Lee el manifest.json generado por `npm run build` para cargar
 * los archivos CSS/JS con hashes de cache busting.
 *
 * En desarrollo (`npm run dev`), carga directamente desde el dev server de Vite
 * para tener HMR (Hot Module Replacement).
 *
 * Para activar modo dev, define en wp-config.php:
 *   define('VITE_DEV_SERVER', true);
 *
 * @package Starter_BS5
 */

defined('ABSPATH') || exit;

class Starter_BS5_Vite
{
    private static ?array $manifest = null;
    private static string $distUri;
    private static string $distPath;
    private static string $devServer = 'http://localhost:5173';

    /**
     * Inicializar paths
     */
    public static function init(): void
    {
        self::$distUri  = STARTER_BS5_URI . '/dist';
        self::$distPath = STARTER_BS5_DIR . '/dist';
    }

    /**
     * ¿Está Vite en modo desarrollo?
     */
    public static function isDev(): bool
    {
        return defined('VITE_DEV_SERVER') && VITE_DEV_SERVER === true;
    }

    /**
     * Cargar el manifest.json
     */
    private static function loadManifest(): array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $manifestPath = self::$distPath . '/.vite/manifest.json';

        if (!file_exists($manifestPath)) {
            // Intentar ruta legacy
            $manifestPath = self::$distPath . '/manifest.json';
        }

        if (!file_exists($manifestPath)) {
            self::$manifest = [];
            return self::$manifest;
        }

        $content = file_get_contents($manifestPath);
        self::$manifest = json_decode($content, true) ?: [];

        return self::$manifest;
    }

    /**
     * Obtener URL de un asset desde el manifest
     */
    public static function asset(string $entry): ?string
    {
        $manifest = self::loadManifest();

        if (!isset($manifest[$entry])) {
            return null;
        }

        return self::$distUri . '/' . $manifest[$entry]['file'];
    }

    /**
     * Obtener CSS asociados a un entry JS
     */
    public static function cssFiles(string $entry): array
    {
        $manifest = self::loadManifest();

        if (!isset($manifest[$entry]['css'])) {
            return [];
        }

        return array_map(function ($css) {
            return self::$distUri . '/' . $css;
        }, $manifest[$entry]['css']);
    }

    /**
     * Enqueue de un entry point de Vite (JS + CSS asociado)
     *
     * @param string $handle    Handle de WordPress
     * @param string $entry     Ruta del entry en src (ej: 'src/js/main.js')
     * @param array  $jsDeps    Dependencias JS
     * @param bool   $inFooter  Cargar JS en footer
     */
    public static function enqueue(string $handle, string $entry, array $jsDeps = [], bool $inFooter = true): void
    {
        // ---------------------------------------------------------------
        // MODO DESARROLLO: cargar desde Vite dev server
        // ---------------------------------------------------------------
        if (self::isDev()) {
            // Vite client (HMR)
            wp_enqueue_script(
                'vite-client',
                self::$devServer . '/@vite/client',
                [],
                null,
                false
            );

            // Entry point
            wp_enqueue_script(
                $handle,
                self::$devServer . '/' . $entry,
                $jsDeps,
                null,
                $inFooter
            );

            // En dev, Vite inyecta el CSS vía JS (HMR), no hace falta enqueue

            return;
        }

        // ---------------------------------------------------------------
        // MODO PRODUCCIÓN: cargar desde dist/ con manifest
        // ---------------------------------------------------------------
        $jsUrl = self::asset($entry);

        if ($jsUrl) {
            wp_enqueue_script(
                $handle,
                $jsUrl,
                $jsDeps,
                null,
                $inFooter
            );

            // Añadir type="module" al script
            add_filter('script_loader_tag', function ($tag, $tagHandle) use ($handle) {
                if ($tagHandle === $handle) {
                    $tag = str_replace(' src', ' type="module" src', $tag);
                }
                return $tag;
            }, 10, 2);
        }

        // CSS asociado al entry
        $cssFiles = self::cssFiles($entry);
        foreach ($cssFiles as $i => $cssUrl) {
            wp_enqueue_style(
                "{$handle}-css-{$i}",
                $cssUrl,
                [],
                null
            );
        }
    }

    /**
     * Enqueue solo de un archivo CSS (como editor.scss)
     */
    public static function enqueueStyle(string $handle, string $entry): void
    {
        if (self::isDev()) {
            // En dev, Vite sirve SCSS compilado on-the-fly
            wp_enqueue_style(
                $handle,
                self::$devServer . '/' . $entry,
                [],
                null
            );
            return;
        }

        $url = self::asset($entry);
        if ($url) {
            wp_enqueue_style($handle, $url, [], null);
        }
    }
}

// Inicializar
Starter_BS5_Vite::init();
