<?php
namespace SCM\Presentation;

class Shortcode
{
  public static function register(): void
  {
    add_shortcode('chat_marco_ai', [self::class, 'render']);
  }

  public static function render(): string
  {
    ob_start(); ?>
    <div class="scm-root">
      <button class="scm-launcher" aria-controls="scm-window" aria-expanded="false"
        title="Iniciar chat con un asesor virtual">
        <span class="scm-launcher-icon" aria-hidden="true">💬</span>
        <span class="scm-launcher-label">Iniciar chat</span>
      </button>

      <div id="scm-window" class="scm-window scm-hidden" role="dialog" aria-modal="true" aria-label="Asistente virtual">
        <div class="scm-window-header">
          <div class="scm-title">Asistente de <?php echo esc_html(get_bloginfo('name')); ?></div>
          <div class="scm-actions">
            <button class="scm-minimize" title="Minimizar">–</button>
            <button class="scm-close" title="Cerrar">×</button>
          </div>
        </div>
        <div class="scm-window-body">
          <div class="scm-messages" aria-live="polite" aria-atomic="false"></div>
        </div>
        <div class="scm-window-footer">
          <form class="scm-form" autocomplete="off">
            <input class="scm-input" type="text" name="message" placeholder="Escribe tu mensaje…" />
            <button class="scm-send" type="submit">Enviar</button>
          </form>
          <div class="scm-end">
            <button class="scm-end-btn" type="button">Finalizar conversación</button>
          </div>
        </div>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
  }
}
