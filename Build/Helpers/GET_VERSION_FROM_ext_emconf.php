#!/usr/bin/env php
<?php
$_EXTKEY = 'solr';
require __DIR__ . '/../../ext_emconf.php';
if (!isset($EM_CONF['solr']['version']) || !is_string($EM_CONF['solr']['version'])) {
    exit(1);
}
echo ($EM_CONF['solr']['version']) . PHP_EOL;
exit(0);
