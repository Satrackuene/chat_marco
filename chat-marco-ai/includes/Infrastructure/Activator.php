<?php
namespace SCM\Infrastructure;

class Activator
{
  public static function activate(): void
  {
    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $charset_collate = $wpdb->get_charset_collate();
    $customers = $wpdb->prefix . 'SCM_customers';
    $sessions = $wpdb->prefix . 'SCM_chat_sessions';
    $messages = $wpdb->prefix . 'SCM_chat_messages';

    $sql = "
        CREATE TABLE {$customers} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(40) NULL,
            name VARCHAR(190) NULL,
            company VARCHAR(190) NULL,
            meta LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY email (email),
            KEY phone (phone)
        ) {$charset_collate};

        CREATE TABLE {$sessions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_uuid VARCHAR(36) NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            channel VARCHAR(20) DEFAULT 'web',
            status VARCHAR(20) DEFAULT 'open',
            satisfaction TINYINT NULL,
            escalated TINYINT(1) DEFAULT 0,
            webhook_sent TINYINT(1) DEFAULT 0,
            started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            ended_at DATETIME NULL,
            meta LONGTEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_uuid (session_uuid),
            KEY customer_id (customer_id)
        ) {$charset_collate};

        CREATE TABLE {$messages} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_uuid VARCHAR(36) NOT NULL,
            role VARCHAR(20) NOT NULL,
            content LONGTEXT NOT NULL,
            response_time_ms INT NULL,
            tokens INT NULL,
            meta LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_uuid (session_uuid),
            KEY role (role)
        ) {$charset_collate};
        ";

    dbDelta($sql);
  }
}
