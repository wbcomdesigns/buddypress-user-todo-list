<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @todo Justify why we need this or remove it. AFAIK nothing can be done with textdomains else than loading it.
 *       This, if true, makes this class a total waste of code.
 *
 * @since      1.0.0
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 * @author     Your Name <email@example.com>
 */
class Bptodo_List_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * NOTE: this method is currently DEAD CODE - Bptodo_List::set_locale() no
	 * longer wires it up, because the domain is loaded by bptodo_load_textdomain()
	 * on init:10 in the main plugin file (the correct timing for WordPress 6.7+;
	 * this class ran on plugins_loaded, which is too early). The domain below was
	 * left at the unedited boilerplate value 'plugin-name', which no string in this
	 * plugin uses; corrected to 'wb-todo' so the file cannot reintroduce a wrong
	 * domain if it is ever re-wired. This whole class is a candidate for removal.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wb-todo',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}