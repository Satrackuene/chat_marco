<?php
namespace SCM\Rest;

use SCM\Infrastructure\Options;
use SCM\Services\RateLimiter;
use SCM\Services\HttpClient;
use SCM\Domain\Repositories\CustomerRepository;
use SCM\Domain\Repositories\SessionRepository;
use SCM\Domain\Repositories\MessageRepository;

class Api
{
  public static function register_routes(): void
  {
    register_rest_route('chat-marco-ia/v1', '/session/start', [
      'methods' => 'POST',
      'permission_callback' => '__return_true',
      'callback' => [self::class, 'session_start']
    ]);

    register_rest_route('chat-marco-ia/v1', '/message', [
      'methods' => 'POST',
      'permission_callback' => '__return_true',
      'callback' => [self::class, 'message']
    ]);

    register_rest_route('chat-marco-ia/v1', '/session/end', [
      'methods' => 'POST',
      'permission_callback' => '__return_true',
      'callback' => [self::class, 'session_end']
    ]);
  }

  private static function uuid4(): string
  {
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  public static function session_start(\WP_REST_Request $req)
  {
    global $wpdb;
    $body = $req->get_json_params();
    $session_uuid = self::uuid4();

    $custRepo = new CustomerRepository($wpdb);
    $sessRepo = new SessionRepository($wpdb);

    $customer_id = null;
    if (is_user_logged_in()) {
      $customer_id = $custRepo->ensureFromWPUser(get_current_user_id());
    }

    $sessRepo->create($session_uuid, $customer_id, isset($body['meta']) ? (array) $body['meta'] : null);

    return new \WP_REST_Response(['session_uuid' => $session_uuid], 201);
  }

  public static function message(\WP_REST_Request $req)
  {
    global $wpdb;
    $opts = Options::get();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!RateLimiter::allow($ip, intval($opts['rate_limit_per_min']))) {
      return new \WP_REST_Response(['error' => 'Rate limit exceeded'], 429);
    }

    $body = $req->get_json_params();
    $session_uuid = sanitize_text_field($body['session_uuid'] ?? '');
    $message = trim((string) ($body['message'] ?? ''));
    $meta = isset($body['meta']) ? (array) $body['meta'] : null;

    if (!$session_uuid || $message === '') {
      return new \WP_REST_Response(['error' => 'Parámetros inválidos'], 400);
    }

    $msgRepo = new MessageRepository($wpdb);
    $msgRepo->insert($session_uuid, 'user', $message, $meta);

    $reply_text = 'Estoy pensando…';
    $sources = [];
    $actions = [];

    $webhook = $opts['n8n_prod_webhook'];
    if ($webhook) {
      $payload = [
        'session_uuid' => $session_uuid,
        'message' => $message,
        'site' => home_url(),
        'locale' => get_locale(),
        'timestamp' => time()
      ];
      $res = HttpClient::postJson($webhook, $payload);
      if (empty($res['error']) && isset($res['code']) && $res['code'] >= 200 && $res['code'] < 300) {
        $data = json_decode($res['body'], true);
        if (is_array($data)) {
          $reply_text = isset($data['reply']) ? (string) $data['reply'] : $reply_text;
          $sources = isset($data['sources']) && is_array($data['sources']) ? $data['sources'] : [];
          $actions = isset($data['actions']) && is_array($data['actions']) ? $data['actions'] : [];
        }
      } else {
        $reply_text = 'Ahora mismo no puedo responder. Intenta de nuevo en unos minutos.';
      }
    } else {
      $reply_text = 'n8n no está configurado. Pide al administrador que agregue la URL del webhook en Ajustes → Nodo Chat Pro.';
    }

    $msgRepo->insert($session_uuid, 'assistant', $reply_text, ['sources' => $sources, 'actions' => $actions]);

    return new \WP_REST_Response([
      'reply' => $reply_text,
      'sources' => $sources,
      'actions' => $actions
    ], 200);
  }

  public static function session_end(\WP_REST_Request $req)
  {
    global $wpdb;
    $opts = Options::get();
    $body = $req->get_json_params();

    $session_uuid = sanitize_text_field($body['session_uuid'] ?? '');
    $satisfaction = isset($body['satisfaction']) ? intval($body['satisfaction']) : null;
    $escalate = !empty($body['escalate']);
    $phone = sanitize_text_field($body['phone'] ?? '');

    if (!$session_uuid) {
      return new \WP_REST_Response(['error' => 'session_uuid requerido'], 400);
    }

    $sessRepo = new SessionRepository($wpdb);
    $msgRepo = new MessageRepository($wpdb);

    $sessRepo->close($session_uuid, $satisfaction, $escalate);

    $messages = $msgRepo->getBySession($session_uuid);

    // Webhook de fin
    if (!empty($opts['n8n_end_webhook'])) {
      $payload = [
        'session_uuid' => $session_uuid,
        'satisfaction' => $satisfaction,
        'escalate' => $escalate,
        'phone' => $phone,
        'site' => home_url(),
        'messages' => $messages
      ];
      \SCM\Services\HttpClient::postJson($opts['n8n_end_webhook'], $payload, [], 20);
    }

    // Escalar a llamada
    if ($escalate && !empty($opts['n8n_schedule_call_hook']) && $phone) {
      \SCM\Services\HttpClient::postJson($opts['n8n_schedule_call_hook'], [
        'session_uuid' => $session_uuid,
        'phone' => $phone,
        'site' => home_url()
      ]);
    }

    return new \WP_REST_Response(['ok' => true], 200);
  }
}
