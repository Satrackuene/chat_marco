<?php
namespace NCP\Domain\Repositories;

class CustomerRepository
{
  private \wpdb $db;
  private string $table;

  public function __construct(\wpdb $db)
  {
    $this->db = $db;
    $this->table = $db->prefix . 'SCM_customers';
  }

  public function ensureFromWPUser(int $wp_user_id): ?int
  {
    $id = $this->db->get_var($this->db->prepare("SELECT id FROM {$this->table} WHERE wp_user_id=%d LIMIT 1", $wp_user_id));
    if ($id)
      return (int) $id;
    $user = get_user_by('id', $wp_user_id);
    $this->db->insert($this->table, [
      'wp_user_id' => $wp_user_id,
      'email' => $user ? $user->user_email : null,
      'name' => $user ? $user->display_name : null
    ]);
    return (int) $this->db->insert_id;
  }
}
