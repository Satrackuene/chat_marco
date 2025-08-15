<?php
namespace SCM\Infrastructure;

class Options
{
  const OPTION_KEY = 'SCM_options';

  public static function defaults(): array
  {
    return [
      'n8n_prod_webhook' => '',
      'n8n_end_webhook' => '',
      'n8n_audit_webhook' => '',
      'n8n_schedule_call_hook' => '',
      'rate_limit_per_min' => 60,
      'retention_days' => 365
    ];
  }

  public static function get(): array
  {
    $opts = get_option(self::OPTION_KEY);
    if (!is_array($opts))
      $opts = [];
    return array_merge(self::defaults(), $opts);
  }

  public static function register_settings(): void
  {
    echo "registro de opciones";
    register_setting('SCM_group', self::OPTION_KEY);

    add_settings_section('SCM_main', 'Conexiones del Chat', function () {
      echo '<p>Configura las URLs de n8n y las políticas de retención.</p>';
    }, 'SCM');

    self::add_field('n8n_prod_webhook', 'n8n Webhook (Producción)');
    self::add_field('n8n_end_webhook', 'Webhook fin de conversación');
    self::add_field('n8n_audit_webhook', 'Webhook auditoría');
    self::add_field('n8n_schedule_call_hook', 'Webhook agendar llamada');

    add_settings_section('SCM_policies', 'Políticas', function () {
      echo '<p>Controles de uso y retención de datos.</p>';
    }, 'SCM');

    self::add_field_number('rate_limit_per_min', 'Límite de mensajes por minuto');
    self::add_field_number('retention_days', 'Retención de datos (días)');
  }

  private static function add_field(string $key, string $label): void
  {
    add_settings_field($key, $label, [self::class, 'render_field_text'], 'SCM', 'SCM_main', ['key' => $key]);
  }

  private static function add_field_number(string $key, string $label): void
  {
    add_settings_field($key, $label, [self::class, 'render_field_number'], 'SCM', 'SCM_policies', ['key' => $key]);
  }

  public static function render_field_text(array $args): void
  {
    $opts = self::get();
    $key = esc_attr($args['key']);
    $val = esc_url($opts[$key]);
    echo "<input type='url' style='width: 100%' name='" . self::OPTION_KEY . "[{$key}]' value='{$val}' placeholder='https://tu-n8n/webhook/...'>";
  }

  public static function render_field_number(array $args): void
  {
    $opts = self::get();
    $key = esc_attr($args['key']);
    $val = intval($opts[$key]);
    echo "<input type='number' min='1' name='" . self::OPTION_KEY . "[{$key}]' value='{$val}'>";
  }

  public static function add_options_page(): void
  {
    add_options_page(
      'Chat Marco IA',
      'Chat Marco IA',
      'manage_options',
      'SCM',
      [self::class, 'render_options_page']
    );
  }

  public static function render_options_page(): void
  {
    if (!current_user_can('manage_options'))
      return; ?>
    <div class="wrap">
      <h1>Chat Marco IA — Ajustes</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('SCM_group');
        do_settings_sections('SCM');
        submit_button();
        ?>
      </form>
      <hr />
      <p><strong>Shortcode:</strong> <code>[chat_marco_ia]</code></p>
    </div>
    <?php
  }
}
