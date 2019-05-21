<?php

interface Upstream {

    public function setup(array $args);

    /**
     * Download content from upstream.
     *
     * @param array $args
     * @param Metadata $metadata - Receive image metadata
     * @return mixed
     */
    public function download(array $args, Metadata &$metadata);

}
