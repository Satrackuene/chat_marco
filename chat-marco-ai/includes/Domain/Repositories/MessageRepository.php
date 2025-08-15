<?php
namespace SCM\Domain\Repositories;

class MessageRepository
{
  private \wpdb $db;
  private string $table;

  public function __construct(\wpdb $db)
  {
    $this->db = $db;
    $this->table = $db->prefix . 'SCM_chat_messages';
  }

  public function insert(string $session_uuid, string $role, string $content, ?array $meta = null): void
  {
    $this->db->insert($this->table, [
      'session_uuid' => $session_uuid,
      'role' => $role,
      'content' => $content,
      'meta' => $meta ? wp_json_encode($meta) : null
    ]);
  }

  public function getBySession(string $session_uuid): array
  {
    $sql = $this->db->prepare("SELECT role, content, created_at FROM {$this->table} WHERE session_uuid=%s ORDER BY id ASC", $session_uuid);
    return (array) $this->db->get_results($sql, ARRAY_A);
  }
}
