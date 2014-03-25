<?php
/*
Plugin Name: Domain Sharding
Plugin URI: http://www.seocom.es
Description: This plugin allows us to change the root domain of images and stylesheets that currently are inside the actual domain and then use a domain sharding structure.
Author: David Garcia
Version: 1.1.0
*/

class domain_sharding
{
	var $_slug;
	var $_slugfile;
	var $_hook;

	var $_check_redirection = false;

	var $main_domain_schema;
	var $main_domain;

	var $home;
	var $home_len;
	var $ds_domain;
	var $ds_max;
	var $ds_exclusions;
	var $aliases_dir;

	var $valid = false;

	function domain_sharding()
	{
		$this->__construct();
	}	
	
	function __construct()
	{
		$this->_slug = basename(dirname(__FILE__));
		$this->_slugfile = $this->_slug . '.php';
		$this->_hook = basename(__FILE__);
		$this->aliases_dir = dirname(__FILE__) . '/aliases/';

		$this->set_home();
		$this->set_ds();

		if ( $this->_check_redirection )
		{
			add_action('init', array(&$this,'redirect_subdomain'), 5 );
		}

		if ( is_admin() )
		{
			add_action('admin_menu', array(&$this, 'admin_menu') );
			add_action('admin_notices', array( $this, 'show_messages' ) );
			add_filter('plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
			return;
		}

		if ( $this->valid )
		{
			add_action('init', array(&$this,'process'), 100);
		}
	}

	function plugin_action_links( $links, $file )
	{
		if ( $file == plugin_basename( dirname(__FILE__). '/' . $this->_slugfile ) ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=' . $this->_slugfile ) . '">'.__( 'Settings' ).'</a>';
		}

		return $links;
	}

	function set_home()
	{
		$this->home = home_url();
		$this->home_len = strlen($this->home);

		$host_parsed = parse_url($this->home);

		$host = $host_parsed['host'] . $host_parsed['path'];

		$this->main_domain = $host;
		$this->main_domain_schema = $host_parsed['scheme'].'://';
	}

	function set_ds()
	{
		$this->ds_domain='';
		$this->ds_max=0;
		$this->ds_exclusions='';
		$this->valid = false;
		$this->_check_redirection = false;

		$option = get_option('domain_sharding_config');
		if ( !is_array($option) )
		{
			return;
		}

		$this->ds_domain = trim($option['domain']);
		$this->ds_max = intval($option['max']);
		$this->ds_exclusions = explode("\n", $option['exclusions']);

		$this->valid = !empty($this->ds_domain);
		$this->_check_redirection = !empty( $option['redirect'] );
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
		if ( !$this->domain_aliases_writable() )
		{
			$msg = sprintf( __( 'You need to set write permissions to the folder <strong>%s</strong>.', $this->_slug ), $this->aliases_dir );
			echo '<div class="error"><p>' . $msg . '</p></div>';
		}
	}

	function options_page()
	{	
		if ( isset($_POST['domain_sharding']) )
		{
			update_option( 'domain_sharding_config', $_POST['domain_sharding'] );
			$this->set_ds();
			print '<div id="message" class="updated fade"><p><strong>'.__('Options updated.', $this->_slug ).'</strong> <a href="'.get_bloginfo('url').'">'.__('View site', $this->_slug ) . ' &raquo;</a></p></div>';
			$this->write_domain_aliases();
		}
		$option = get_option('domain_sharding_config');

		$verify_result = '';
		if ( isset($_POST['domain_sharding_check']) )
		{
			if ( function_exists('gethostbyname') )
			{
				if ( ! (empty($option['domain']) || empty($option['max']) ) )
				{
					$plugin_url = plugin_dir_url(__FILE__);

					// Get the contents of the test file.
					$main_check_file = 'domain.check.valid.txt';
					$main_check_value = wp_remote_get($plugin_url . $main_check_file );
					if ( is_array( $main_check_value ) && $main_check_value['response']['code']=='200' )
					{
						$main_check_value = $main_check_value['body'];
					} else {
						$main_check_value = '';
					}

					if ( !empty($main_check_value) )
					{
						$main_domain = parse_url( home_url(), PHP_URL_HOST );
						$main_domain_ip = gethostbyname( $main_domain );

						$verify_result .= '<p>'.__('Main domain ip',$this->_slug).': <strong>'.$main_domain_ip.'</strong></p>';
						$verify_result .= '<p>'.__('Verifying subdomains',$this->_slug).':</p>';

						for($x=1;$x<=intval($option['max']);$x++)
						{

							$ok = true;

							$domain = $this->ds_build_domain( $x, false );
							$verify_result .= $domain . ': ';
							$domain_ip = gethostbyname( $domain );
							if ( $domain_ip != $main_domain_ip )
							{
								$verify_result .= ' ' . sprintf( __('The subdomain ip <strong>%s</strong> differs from the ip of main domain.',$this->_slug), $domain_ip );
								$ok = false;
							}

							if ( $ok )
							{
								$domain_plugin_url = str_replace($main_domain, $domain, $plugin_url );
								$check_value = wp_remote_get($plugin_url . $main_check_file );
								if ( is_array( $check_value ) && $check_value['response']['code']=='200' )
								{
									$check_value = $check_value['body'];
								} else {
									$check_value = '';
								}
								if ( $check_value != $main_check_value )
								{
									$verify_result .= ' <strong style="color:#ACA700">'.__('Subdomain does not seem to point to the same physical directory of the main domain', $this->_slug).'.</strong>';	
									$ok = false;
								}
							}

							if ( $ok )
							{
								$verify_result .= ' <strong style="color:#4DD25C">'.__('Is valid', $this->_slug).'.</strong>';
							} else {
								$verify_result .= ' <strong style="color:#F00;text-decoration:uppercase;">'.__('Is not valid', $this->_slug).'!!!</strong>';
							}

							$verify_result .= '<br/>';

						}
					} else {
						$verify_result .= ' <strong style="color:#F00;text-decoration:uppercase;">'.__('The file used to check the subdomain configuration is missing. Try reinstalling the plugin.', $this->_slug).'</strong>';
					}
				}
			} else {
				$verify_result .= ' <strong style="color:#F00;text-decoration:uppercase;">'.__('Your PHP installation does not allow us to use the gethostbyname function.', $this->_slug).'</strong>';
			}
		}
	
		$redirect_checked = '';

		if ( !empty($option['redirect']) )
		{
			$redirect_checked = ' checked="checked"';
		}

		$sharding_alias = dirname(__FILE__).'/';
		$sharding_alias = str_replace(ABSPATH, '', $sharding_alias);

		print '
		<div class="wrap">
		<h2>Domain Sharding Settings</h2>

		<form method="post" action="http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'">
		<table class="form-table">
		<tr valign="top">
			<th scope="row">'.__('Domain', $this->_slug ).'</th>
			<td>
			<input id="domain_sharding_domain" name="domain_sharding[domain]" class="regular-text" value = "'. $option['domain'].'" />
			<p>'.__('You can use the # character to mark the point where the domain random number will be inserted.', $this->_slug ). ' ' .__('Eg: http://img#.domain.tld', $this->_slug ).'</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">'.__('Max domains', $this->_slug ).'</th>
			<td><input id="domain_sharding_max" name="domain_sharding[max]" class="regular-text" value = "'. $option['max'].'" /></td>
		</tr>
		<tr valign="top">
			<th scope="row">'.__('Exclusions', $this->_slug ).'</th>
			<td>
			<textarea id="domain_sharding_exclusions" name="domain_sharding[exclusions]" class="large-text" rows="10" cols="50">'. $option['exclusions'].'</textarea>
			<p>'.__('Do not transform urls containing this words. One exception per line.', $this->_slug ).'</p>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row">'.__('Redirect if the http host is not the blog domain', $this->_slug ).'</th>
			<td>
			<input type="checkbox" id="domain_sharding_redirect" name="domain_sharding[redirect]" value = "1" '.$redirect_checked.' />
			<p>'.sprintf( __('Do a 301 redirection to the main address if the blog is not visited using the address <strong>%s</strong>', $this->_slug ), home_url() ).'</p>
			</td>
		</tr>
		</table>
		<p>'.__('<strong>NOTE:</strong> If you have trouble accessing images using the new address because Wordpress asks you to register the domain then you must insert the following line in the file wp-config.php').'</p>
		<p><pre>include_once(ABSPATH.\''.$sharding_alias.'domain-sharding-alias.php\');</pre></p>
		<p>'.__('<strong>NOTE:</strong> You\'ll need to manually create the new A records for the subdomains in your DNS panel. They should have the same ip address of your main domain.').'</p>
		<p class="submit"><input type="submit" value="Submit &raquo;" class="button button-primary"/></p>
		</form>

		<p>&nbsp;</p>

		<form method="post" action="http://'.$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'].'">
		<p class="submit"><input type="submit" value="'.__('Verify subdomains', $this->_slug).'" class="button button-primary" name="domain_sharding_check" /></p>
		'.$verify_result.'
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

				$exclude = false;
				if ( !empty($this->ds_exclusions) )
				{
					foreach( $this->ds_exclusions as $exclusion )
					{
						if ( stripos( $original, $exclusion) !== false )
						{
							$exclude = true;
							break;
						}
					}
				}

				if ( $exclude )
				{
					continue;
				}

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

	function ds_calc_number( $value )
	{
		$number = abs( ( crc32( $value ) % $this->ds_max ) ) + 1;
		return $number;
	}

	function ds_build_domain( $number, $use_schema = true )
	{
		$domain = str_replace('#', $number, $this->ds_domain);

		$host_parsed = parse_url($domain);

		$domain = $host_parsed['host'] . $host_parsed['path'];
		if ( $use_schema )
		{
			if ( empty($host_parsed['scheme']) )
			{
				$scheme = $this->main_domain_schema;
			} else {
				$scheme = $host_parsed['scheme'].'://';
			}
			$domain = $scheme . $domain;
		}
		return $domain;
	}

	function ds_parse( $value )
	{	
		if ( substr( $value, 0, $this->home_len ) != $this->home )
		{
			return $value;
		}
		$number = $this->ds_calc_number( $value );

		$domain = $this->ds_build_domain($number);
		$domain = $domain . substr( $value, $this->home_len );

		return $domain;
	}

	function redirect_subdomain()
	{
		$main_url = home_url();
		$main_domain = parse_url( $main_url, PHP_URL_HOST );

		if ( $_SERVER['HTTP_HOST'] != $main_domain )
		{
			wp_redirect($main_url, 301);
			die;
		}
		if ( isset($_SERVER['X-DS-HTTP_HOST']) && $_SERVER['X-DS-HTTP_HOST'] != $main_domain )
		{
			wp_redirect($main_url, 301);
			die;
		}


	}

	function domain_aliases_writable()
	{
		return is_writable($this->aliases_dir);
	}

	function write_domain_aliases()
	{
		if ( !$this->domain_aliases_writable() )
		{
			return false;
		}

		$blog_id = get_current_blog_id();
		$option = get_option('domain_sharding_config');
		$main_domain = parse_url( home_url(), PHP_URL_HOST );

 		$alias_file = $this->aliases_dir . sprintf('alias-domain-%d.php', $blog_id);

 		$max = intval($option['max']);

		if ( !$max )
		{
			@unlink($alias_file);
			return;
		}

		$data = "<?php
if ( in_array(\$_SERVER"."['HTTP_HOST'], array(
";

		for($x=1;$x<=$max;$x++)
		{
			$domain = $this->ds_build_domain( $x, false );
			$data .= "'$domain',\n";
		}

		$data .= ")) ){\$_SERVER"."['X-DS-HTTP_HOST']=\$_SERVER"."['HTTP_HOST'];\$_SERVER"."['HTTP_HOST']='$main_domain';}";

		file_put_contents($alias_file, $data);
	}
}

$domain_sharding = new domain_sharding();
