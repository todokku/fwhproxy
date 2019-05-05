<?php

interface Storage {

    function init();

    function load_config($name);

    function save_config($name, $config);

    function close();

}
