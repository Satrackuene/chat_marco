<?php
namespace SCM\Presentation;

class Assets
{
  public static function enqueue(): void
  {
    if (is_admin())
      return;

    wp_enqueue_style('SCM-chat-css', SCM_URL . 'assets/chat.css', [], SCM_VERSION);
    wp_enqueue_script('SCM-chat-js', SCM_URL . 'assets/chat.js', [], SCM_VERSION, true);

    $data = [
      'rest_base' => esc_url_raw(rest_url('nodo-chat-pro/v1')),
      'nonce' => wp_create_nonce('wp_rest'),
      'site' => ['name' => get_bloginfo('name')]
    ];
    wp_localize_script('SCM-chat-js', 'SCM', $data);
  }
}
