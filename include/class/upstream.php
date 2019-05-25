<?php

interface Upstream {

    /**
     * Download content from upstream.
     *
     * @param $opts
     * @param Metadata $metadata - Receive image metadata
     * @return mixed
     */
    public function download(Options $opts, Metadata &$metadata);

}
