<?php
class ControllerStartupSecurityHeaders extends Controller {
    public function index() {
        try {
            (new \CodeCart\Core\SecurityHeaders($this->registry))->apply(false);
        } catch (\Throwable $e) {
            // Fail open for storefront compatibility; invalid policy is reported through logs/preflight.
            if (isset($this->log)) {
                $this->log->write('Security headers: ' . $e->getMessage());
            }
        }
    }
}
