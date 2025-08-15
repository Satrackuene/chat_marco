<?php
namespace SCM\Domain\Repositories;

class SessionRepository
{
  private \wpdb $db;
  private string $table;

  public function __construct(\wpdb $db)
  {
    $this->db = $db;
    $this->table = $db->prefix . 'SCM_chat_sessions';
  }

  public function create(string $session_uuid, ?int $customer_id, ?array $meta = null): void
  {
    $this->db->insert($this->table, [
      'session_uuid' => $session_uuid,
      'customer_id' => $customer_id,
      'channel' => 'web',
      'status' => 'open',
      'meta' => $meta ? wp_json_encode($meta) : null
    ]);
  }

  public function close(string $session_uuid, ?int $satisfaction, bool $escalate): void
  {
    $this->db->update($this->table, [
      'status' => 'closed',
      'satisfaction' => $satisfaction,
      'escalated' => $escalate ? 1 : 0,
      'ended_at' => current_time('mysql')
    ], ['session_uuid' => $session_uuid]);
  }
}
