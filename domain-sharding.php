<?php
/*
Plugin Name: Domain Sharding
Description: This plugin allows us to change the root domain of images and stylesheets that currently are inside the actual domain and then use a domain sharding structure.
Author: David Garcia
Version: 1.0.0
*/

class domain_sharding
{
	var $_slug;
	var $_hook;

	var $main_domain_schema;
	var $main_domain;

	var $home;
	var $home_len;
	var $ds_domain;
	var $ds_max;

	var $valid = false;

	function domain_sharding()
	{
		$this->__construct();
	}	
	
	function __construct()
	{
		$this->_slug = basename(dirname(__FILE__));
		$this->_hook = basename(__FILE__);

		$this->set_home();
		$this->set_ds();

		if ( is_admin() )
		{
			add_action('admin_menu', array(&$this, 'admin_menu') );
			add_action('admin_notices', array( $this, 'show_messages' ) );

			return;
		}

		if ( $this->valid )
		{
			add_action('init', array(&$this,'process'), 100);
		}
	}

	function set_home()
	{
		$this->home = home_url();
		$this->home_len = strlen($this->home);

		$host_parsed = parse_url($this->home);
		$host = array_reverse( explode('.', $host_parsed['host'] ) );
		$host = $host[1].'.'.$host[0];

		$this->main_domain = $host;
		$this->main_domain_schema = $host_parsed['scheme'].'://';
	}

	function set_ds()
	{
		$option = get_option('domain_sharding_config');
		if ( !is_array($option) )
		{
			return;
		}
		$this->ds_domain = trim($option['domain']);
		$this->ds_max = intval($option['max']);

		$this->valid = !empty($this->ds_domain);
	}

	function admin_menu()
	{
		add_submenu_page( 'options-general.php', 'Domain Sharding', 'Domain Sharding', 10, 'domain-sharding.php', array(&$this, 'options_page') );
	}

	function show_messages()
	{
		if ( !$this->valid ) {
			$msg = sprintf( __( 'You need to <a href="%s">set up your Domain Sharding settings</a>.', $this->_slug ), $this->get_options_url() );
			echo '<div class="error"><p>' . $msg . '</p></div>';
		}
	}

	function options_page()
	{	
		if ( isset($_POST['domain_sharding']) )
		{
			update_option( 'domain_sharding_config', $_POST['domain_sharding'] );
			print '<div id="message" class="updated fade"><p><strong>'.__('Options updated.', $this->_slug ).'</strong> <a href="'.get_bloginfo('url').'">'.__('View site', $this->_slug ) . ' &raquo;</a></p></div>';
		}
		$option = get_option('domain_sharding_config');
	
		print '
		<div class="wrap">
		<h2>Domain Sharding Settings</h2>

		<form method="post" action="http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'">
		<table class="form-table">
		<tr valign="top">
			<th scope="row">Domain</th>
			<td><input id="domain_sharding_domain" name="domain_sharding[domain]" class="regular-text" value = "'. $option['domain'].'" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">Max domains</th>
			<td><input id="domain_sharding_max" name="domain_sharding[max]" class="regular-text" value = "'. $option['max'].'" /></td>
		</tr>
		</table>
		<p>'.__('The final domain will follow the structure', $this->_slug ).' http://[domain][1-MaxDomain].[basedomain]</p>
		<p class="submit"><input type="submit" value="Submit &raquo;" class="button button-primary"/></p>
		</form>

		</div>
		';
	}

	function get_options_url()
	{
		return admin_url( 'options-general.php?page=' . $this->_hook );
	}


	function process()
	{
		ob_start(array($this,'check_domain_sharding'));
	}
	
	function check_domain_sharding(&$buffer)
	{
		if ( $buffer != '' )
		{
			preg_match_all('/src\s*=\s*"([^"]+)"/sim', $buffer, $result, PREG_SET_ORDER);

			$urls=array();
			foreach( $result as $row )
			{
				$urls[] = $row[1];
			}

			$urls=array_unique($urls);

			foreach( $urls as $url )
			{
				$original = $url;
				if ( substr($url,0,1) == '/' )
				{
					$url = "/$url";
				}
				if ( substr($url,0,4) != 'http' )
				{
					$url = $home.$url;
				}
				$url = $this->ds_parse( $url );

				if ( $original == $url )
				{
					continue;
				}
				//return '"'.$original . "\n\n" . '"'.$url;


				$buffer = str_ireplace( '"'.$original, '"'.$url, $buffer );
			}
		}
		
		return $buffer;
	}

	function ds_calc_subdomain( $value, $sub = 'cdn' )
	{
		$number = abs( ( crc32( $value ) % $this->ds_max ) ) + 1;
		
		return $sub.$number;
	}
	function ds_parse( $value )
	{	
		if ( substr( $value, 0, $this->home_len ) != $this->home )
		{
			return $value;
		}

		$domain = $this->main_domain_schema.$this->ds_calc_subdomain( $value, $this->ds_domain ).'.'.$this->main_domain;

		$domain = $domain . substr( $value, $this->home_len );

		return $domain;
	}
}

$domain_sharding = new domain_sharding();
