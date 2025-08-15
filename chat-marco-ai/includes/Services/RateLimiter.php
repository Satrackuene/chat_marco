<?php
namespace SCM\Services;

class RateLimiter
{
  public static function allow(string $ip, int $limitPerMin = 60): bool
  {
    $limit = max(1, $limitPerMin);
    $key = 'SCM_rl_' . md5($ip);
    $count = (int) get_transient($key);
    if ($count >= $limit)
      return false;
    set_transient($key, $count + 1, MINUTE_IN_SECONDS);
    return true;
  }
}
