<?php
namespace SCM\Presentation;

class Shortcode
{
  public static function register(): void
  {
    add_shortcode('chat_marco_ia', [self::class, 'render']);
  }

  public static function render(): string
  {
    echo 1;
    ob_start(); ?>
    <div class="SCM-root">
      <button class="SCM-launcher" aria-controls="SCM-window" aria-expanded="false"
        title="Iniciar chat con un asesor virtual">
        <span class="SCM-launcher-icon" aria-hidden="true">💬</span>
        <span class="SCM-launcher-label">Iniciar chat</span>
      </button>

      <div id="SCM-window" class="SCM-window SCM-hidden" role="dialog" aria-modal="true" aria-label="Asistente virtual">
        <div class="SCM-window-header">
          <div class="SCM-title">Asistente de <?php echo esc_html(get_bloginfo('name')); ?></div>
          <div class="SCM-actions">
            <button class="SCM-minimize" title="Minimizar">–</button>
            <button class="SCM-close" title="Cerrar">×</button>
          </div>
        </div>
        <div class="SCM-window-body">
          <div class="SCM-messages" aria-live="polite" aria-atomic="false"></div>
        </div>
        <div class="SCM-window-footer">
          <form class="SCM-form" autocomplete="off">
            <input class="SCM-input" type="text" name="message" placeholder="Escribe tu mensaje…" />
            <button class="SCM-send" type="submit">Enviar</button>
          </form>
          <div class="SCM-end">
            <button class="SCM-end-btn" type="button">Finalizar conversación</button>
          </div>
        </div>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
  }
}
