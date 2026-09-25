<?php
class ControllerStartupSecurityHeaders extends Controller {
    public function index() {
        try {
            (new \CodeCart\Core\SecurityHeaders($this->registry))->apply(true);
        } catch (\Throwable $e) {
            // Security headers must never make the admin unavailable because of a bad setting.
            if (isset($this->log)) {
                $this->log->write('Security headers: ' . $e->getMessage());
            }
        }
    }
}
