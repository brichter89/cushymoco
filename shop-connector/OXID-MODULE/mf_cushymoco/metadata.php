<?php

$sMetadataVersion = '1.1';

$sModuleName = 'mf_cushymoco';

$aModule = array(
    'id'          => $sModuleName,
    'title'       => 'Cushymoco Shop Connector',
    'description' => 'Enables the API for the Cushymoco Mobile App',
    'version'     => '0.0.1',
    'author'      => 'Björn Richter',
    'url'         => 'http://cushymoco.com/',
    'email'       => '',
    'lang'        => 'en',
    'extend'      => array(
    ),
    'files'       => array(
        'cushymoco'             => $sModuleName . '/application/controllers/cushymoco.php',
        'VersionLayerInterface' => $sModuleName . '/core/interface/VersionLayerInterface.php',
        'VersionLayer460'       => $sModuleName . '/core/VersionLayer460.php',
        'VersionLayer470'       => $sModuleName . '/core/VersionLayer470.php',
        'VersionLayer500'       => $sModuleName . '/core/VersionLayer500.php',
    ),
    'templates'   => array(
        'cushymoco.tpl' => $sModuleName . '/application/views/tpl/cushymoco.tpl',
    ),
);
