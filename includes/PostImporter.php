<?php

namespace WPAutoPosts;

use Exception;

class WPAutoPostsImporter
{
    /**
     * Основна логіка імпорту
     */
    public function import()
    {
        if (!function_exists('post_exists')) {
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }

        $logger = new WPAutoPostsLogger();

        $imported_count = 0;
        $skipped_count = 0;
        $imported_posts = [];

        try {
            $api = new WPAutoPostsAPIClient();
            $posts = $api->fetchPosts();

            foreach ($posts as $post_data) {
                if (post_exists($post_data['title'])) {
                    $skipped_count++;
                    continue;
                }

                $category_id = $this->getOrCreateCategory($post_data['category']);
                $author_id = $this->getAdminUserID();

                $post_id = wp_insert_post([
                    'post_title' => $post_data['title'],
                    'post_content' => $post_data['content'] ?? '',
                    'post_status' => 'publish',
                    'post_author' => $author_id,
                    'post_category' => [$category_id],
                    'post_date' => $this->getRandomDateLastMonth()
                ]);

                if (!is_wp_error($post_id)) {
                    $imported_count++;
                    $imported_posts[] = $post_data;

                    # Додаємо зображення поста
                    if (isset($post_data['image'])) {
                        $media = new WPAutoPostsMediaHandler();
                        $media->attachImage($post_id, $post_data['image']);
                    }

                    # Додаємо мета-поля (site_link)
                    if (!empty($post_data['site_link'])) {
                        update_post_meta($post_id, 'site_link', sanitize_text_field($post_data['site_link']));
                    }

                    # Додаємо мета-поля (rating)
                    if (isset($post_data['rating']) && is_numeric($post_data['rating'])) {
                        update_post_meta($post_id, 'rating', floatval($post_data['rating']));
                    }
                }
            }

            # Успіх імпорту
            $logger->logImport(
                '✅ успішно',
                $imported_count,
                $skipped_count,
                $imported_posts
            );
        } catch (Exception $e) {
            # Провал імпорту
            $logger->logImport(
                '❌ Помилка!',
                0,
                0,
                [],
                $e->getMessage()
            );
        }
    }

    /**
     * Перевірка та створення категорії
     * пошук категорії якщо існує по назві
     */
    private function getOrCreateCategory($name)
    {
        $term = term_exists($name, 'category');
        if ($term !== 0 && $term !== null) {
            return $term['term_id'];
        }

        $new_term = wp_insert_term($name, 'category');
        return is_wp_error($new_term) ? 1 : $new_term['term_id'];
    }

    /**
     * Вертає id адміністратора першого з масиву адмінів
     */
    private function getAdminUserID()
    {
        $admins = get_users(['role' => 'administrator', 'number' => 1]);
        return $admins ? $admins[0]->ID : 1;
    }

    /**
     * Рандомна дата та час від сьогодні до мінус один місяць 30 днів
     **/
    private function getRandomDateLastMonth()
    {
        $days_ago = rand(1, 30);
        $random_time = rand(0, 86399);
        $timestamp = strtotime("-$days_ago days") + $random_time;

        return date('Y-m-d H:i:s', $timestamp);
    }
}
