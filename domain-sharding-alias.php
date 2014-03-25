<?php

$__aliases_dir = dirname(__FILE__) . '/aliases/';
$__aliases = glob( $__aliases_dir . 'alias-domain-*' );

foreach( $__aliases as $__alias )
{
	@include_once $__alias;
}