<?php
class ControllerStartupSass extends Controller {
    public function index() {
        // RC22+: compiled CSS ships with the build. SASS is never compiled during
        // a normal admin request. Use Developer > Rebuild styles or CLI styles:rebuild.
        return;
    }
}
