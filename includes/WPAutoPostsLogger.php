<?php

namespace WPAutoPosts;

class WPAutoPostsLogger
{
    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'auto_posts_log';

        # меню в адмінці
        add_action('admin_menu', [$this, 'addAdminPage']);

        $this->maybeCreateTable();
    }

    /**
     * Створення таблиці, якщо не існує
     */
    private function maybeCreateTable()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            run_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL,
            imported_count INT DEFAULT 0,
            skipped_count INT DEFAULT 0,
            titles_json LONGTEXT,
            error_message TEXT NULL
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Запис логів у таблицю
     */
    public function logImport($status, $imported_count, $skipped_count, $posts = [], $error = null)
    {
        global $wpdb;

        $titles = array_map(function ($post) {
            return [
                'title' => $post['title'],
                'category' => $post['category'] ?? null
            ];
        }, $posts);

        $wpdb->insert($this->table, [
            'run_at' => current_time('mysql'),
            'status' => $status,
            'imported_count' => $imported_count,
            'skipped_count' => $skipped_count,
            'titles_json' => wp_json_encode($titles),
            'error_message' => $error
        ]);

        # Після вставки — очистити зайві
        $this->cleanOldLogs();
    }

    /**
     * Видалити логи, залишивши тільки останні 5
     */
    private function cleanOldLogs()
    {
        global $wpdb;
        $limit = WP_AUTO_POST_PLUGIN_LIMIT_LOG_DB;

        $ids_to_keep = $wpdb->get_col(
            "SELECT id FROM {$this->table} ORDER BY run_at DESC LIMIT {$limit}"
        );

        if (!empty($ids_to_keep)) {
            $ids = implode(',', array_map('intval', $ids_to_keep));
            $wpdb->query("DELETE FROM {$this->table} WHERE id NOT IN ($ids)");
        }
    }

    /**
     * Додає сторінку в адмін-меню
     */
    public function addAdminPage()
    {
        add_menu_page(
            '🛠️ Логи імпорту',
            'WP Auto Posts Importer',
            'manage_options',
            'wp-auto-posts-log',
            [$this, 'renderAdminPage'],
            'dashicons-list-view',
            30
        );
    }

    /**
     * Вивід логів на сторінці в адмінці
     */
    public function renderAdminPage()
    {
        global $wpdb;
        $logs = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY run_at DESC", ARRAY_A);

        echo '<div class="wrap"><h1>Логи імпорту постів (WP Auto Posts Importer)</h1>';
        if (empty($logs)) {
            echo '<p>Логів ще немає.</p></div>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead><tr>
            <th>Дата</th>
            <th>Статус</th>
            <th>Імпортовано</th>
            <th>Пропущено</th>
            <th>Заголовки</th>
            <th>Помилка</th>
        </tr></thead><tbody>';

        foreach ($logs as $log) {
            $titles = json_decode($log['titles_json'], true);
            $titleList = '';

            if ($titles && is_array($titles)) {
                foreach ($titles as $t) {
                    $titleList .= '<li><strong>' . esc_html($t['title']) . '</strong>';
                    if (!empty($t['category'])) {
                        $titleList .= ' <em>(' . esc_html($t['category']) . ')</em>';
                    }
                    $titleList .= '</li>';
                }
            }

            echo '<tr>';
            echo '<td>' . esc_html($log['run_at']) . '</td>';
            echo '<td>' . esc_html($log['status']) . '</td>';
            echo '<td>' . intval($log['imported_count']) . '</td>';
            echo '<td>' . intval($log['skipped_count']) . '</td>';
            echo '<td><ul style="margin:0;padding-left:20px;">' . $titleList . '</ul></td>';
            echo '<td><code>' . esc_html($log['error_message']) . '</code></td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }
}
