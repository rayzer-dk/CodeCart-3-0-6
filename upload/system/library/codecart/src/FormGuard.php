<?php
namespace CodeCart\Core;

/**
 * Backward-compatible facade. Existing controllers and third-party integrations
 * may continue to instantiate FormGuard while the implementation is centralized.
 */
class FormGuard {
    private $service;

    public function __construct($registry) {
        $this->service = new SpamService($registry);
    }

    public function consume($scope, $limit = 30, $window = 600, $sessionInterval = 2) {
        return $this->service->consume((string)$scope, (int)$limit, (int)$window, (int)$sessionInterval);
    }
}
