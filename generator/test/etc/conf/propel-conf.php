<?php
// This file generated by Propel convert-props target on 05/15/05 03:19:49
// from XML runtime conf file .\projects\bookstore\runtime-conf.xml
return array (
  /*
  'log' =>
  array (
    'ident' => 'propel-bookstore',
    'level' => '7',
  ),
  */
  'propel' =>
  array (
    'datasources' =>
    array (
      'bookstore' =>
      array (
        'adapter' => 'mysql',
        'connection' =>
        array (
          'phptype' => 'mysql',
          'hostspec' => 'localhost',
          'database' => 'bookstore',
          'username' => '',
          'password' => '',
        ),
      ),
      'default' => 'bookstore',
    ),
  ),
);