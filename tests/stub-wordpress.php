<?php

declare(strict_types=1);

namespace {
    if (!function_exists('apply_filters')) {
        function apply_filters($hook, $value, ...$args)
        {
            return $value;
        }
    }

    if (!class_exists('wpdb', false)) {
        class wpdb
        {
            public string $posts = 'wp_posts';

            /** @var list<string> */
            public array $executedQueries = [];

            public function get_results($query)
            {
                $this->executedQueries[] = (string) $query;

                return [];
            }
        }
    }
}
