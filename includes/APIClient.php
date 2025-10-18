<?php

namespace WPAutoPosts;

class WPAutoPostsAPIClient
{
    private const API_URL = 'https://my.api.mockaroo.com/posts.json'; # апі для отримання постів
    private const API_KEY = '413dfbf0'; # ключ доступу до апі

    public function fetchPosts()
    {
        $response = wp_remote_get(self::API_URL, [
            'headers' => ['X-API-Key' => self::API_KEY]
        ]);

        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
