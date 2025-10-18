<?php

namespace WPAutoPosts;

use WP_Query;

class WPAutoPostsShortcode
{
    private static bool $add_shortcode_styles = false;

    public function __construct()
    {
        add_shortcode('auto_posts_list_show', [$this, 'renderShortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Вивід шорткода
     */
    public function renderShortcode($atts)
    {
        self::$add_shortcode_styles = true;

        $atts = shortcode_atts([
            'title' => 'Articles',
            'count' => 5,
            'sort' => 'date',
            'ids' => ''
        ], $atts, 'auto_posts_list_show');

        $args = [
            'post_type' => 'post',
            'posts_per_page' => intval($atts['count']),
        ];

        // Якщо передано IDs — беремо тільки їх
        if (!empty($atts['ids'])) {
            $ids = array_map('intval', explode(',', $atts['ids']));
            $args['post__in'] = $ids;
            $args['orderby'] = 'post__in';
        } else {
            switch ($atts['sort']) {
                case 'title':
                    $args['orderby'] = 'title';
                    $args['order'] = 'ASC';
                    break;
                case 'rating':
                    $args['meta_key'] = 'rating';
                    $args['orderby'] = [
                        'meta_value_num' => 'DESC',
                        # в тз не вказано, але якщо виводиться багато постів з однаковим рейтингом
                        # вони на другому рівні будуть сортуватись по даті між собою
                        'date' => 'DESC'
                    ];
                    break;
                default:
                    $args['orderby'] = 'date';
                    $args['order'] = 'DESC';
            }
        }

        $query = new WP_Query($args);

        ob_start();

        echo '<div class="wp-auto-posts">';

        if (!empty($atts['title'])) {
            echo '<div class="auto-posts-heading">' . esc_html($atts['title']) . '</div>';
        }

        if ($query->have_posts()) {
            echo '<div class="auto-posts-wrapper">';
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();

                $post_title = esc_html(get_the_title());
                $image = get_the_post_thumbnail_url($post_id, 'medium');
                $category = get_the_category();
                $category_name = $category ? esc_html($category[0]->name) : '';
                $rating = get_post_meta($post_id, 'rating', true);
                $site_link = get_post_meta($post_id, 'site_link', true);

                ?>
                <div class="auto-post-item">
                    <div class="auto-post-thumb">
                        <?php if ($image) { ?>
                            <img src="<?= esc_url($image) ?>" alt="<?= esc_attr(get_the_title()) ?>">
                        <?php } else { ?>
                            <div class="no-image">No image</div>
                        <?php } ?>
                    </div>
                    <div class="auto-post-content">
                        <div class="auto-post-category"><?= $category_name ?></div>
                        <div class="auto-post-title">
                            <?= !empty($post_title)
                                ? $this->truncateTitleByWords($post_title, 55) : '' ?>
                        </div>
                        <a class="auto-post-readmore" href="<?= esc_url(get_permalink()) ?>">Read More</a>
                        <div class="auto-post-wrap">
                            <?php if ($rating) { ?>
                                <div class="auto-post-rating">
                                    <span>⭐</span> <?= esc_html($rating) ?>
                                </div>
                            <?php } ?>
                            <?php if ($site_link) { ?>
                                <a class="auto-post-button"
                                   rel="nofollow"
                                   href="<?= esc_url($site_link) ?>"
                                   target="_blank">Visit Site</a>
                            <?php } else { ?>
                                <div class="no-link"></div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>No posts found.</p>';
        }
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Обрізання строки по слову за кількістю символів
     * прийшлось обрізати тайтли, бо в дизайні не передбачено великі тайтли у постів які повертає апі
     */
    private function truncateTitleByWords($title, $limit = 50)
    {
        if (strlen($title) <= $limit) {
            return $title;
        }

        # Обрізаємо до максимально допустимої довжини
        $truncated = substr($title, 0, $limit);

        # Шукаємо останній пробіл, щоб не обрізати слово
        $last_space = strrpos($truncated, ' ');
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }

        return $truncated . '...';
    }

    /**
     * Завантаження стилів
     */
    public function enqueueAssets()
    {
        if (self::$add_shortcode_styles) {
            wp_enqueue_style(
                'auto-posts-shortcode-style',
                plugin_dir_url(__FILE__) . '../assets/shortcode-style.css',
                [],
                '1.0'
            );
        }
    }
}
