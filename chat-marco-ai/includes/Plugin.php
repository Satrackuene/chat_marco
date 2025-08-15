<?php
namespace SCM;

use SCM\Infrastructure\Options;
use SCM\Presentation\Assets;
use SCM\Presentation\Shortcode;
use SCM\Rest\Api;

class Plugin
{
  public function init(): void
  {
    echo "inicio de plugin";
    // Admin options page + settings
    add_action('admin_init', [Options::class, 'register_settings']);
    add_action('admin_menu', [Options::class, 'add_options_page']);

    // Assets
    add_action('wp_enqueue_scripts', [Assets::class, 'enqueue']);

    // Shortcode
    add_action('init', [Shortcode::class, 'register']);

    // REST API routes
    add_action('rest_api_init', [Api::class, 'register_routes']);
  }
}
