<?php

interface Upstream {

    public function setup(array $args);

    public function fetch(array $args);

}
