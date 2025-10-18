<?php

namespace WPAutoPosts;

class WPAutoPostsMediaHandler
{
    public function attachImage($post_id, $image_url)
    {
        # функції WordPress для роботи та завантаження медіа
        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        # Звантажуємо зображення
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            @unlink($tmp);
            return;
        }

        # Ставимо фіктивну назву з правильним розширенням .png
        $file_array = [
            'name' => uniqid('image_') . '.png',
            'tmp_name' => $tmp
        ];

        # Дозволяємо PNG на додавання у вп
        add_filter('upload_mimes', function ($mimes) {
            $mimes['png'] = 'image/png';
            return $mimes;
        });

        # Спроба завантажити в Media
        $attachment_id = media_handle_sideload($file_array, $post_id);

        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return;
        }

        # Генерація метаданих
        $metadata = wp_generate_attachment_metadata($attachment_id, get_attached_file($attachment_id));
        wp_update_attachment_metadata($attachment_id, $metadata);

        # Встановлюємо як зображення поста
        set_post_thumbnail($post_id, $attachment_id);
    }
}
