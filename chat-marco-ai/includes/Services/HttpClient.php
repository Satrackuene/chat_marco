<?php
namespace SCM\Services;

class HttpClient
{
  public static function postJson(string $url, array $payload, array $headers = [], int $timeout = 15): array
  {
    $args = [
      'headers' => array_merge(['Content-Type' => 'application/json'], $headers),
      'body' => wp_json_encode($payload),
      'timeout' => $timeout
    ];
    $res = wp_remote_post($url, $args);
    if (is_wp_error($res)) {
      return ['error' => $res->get_error_message()];
    }
    $code = wp_remote_retrieve_response_code($res);
    $body = wp_remote_retrieve_body($res);
    return ['code' => $code, 'body' => $body];
  }
}
