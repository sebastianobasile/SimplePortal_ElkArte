<?php

/**
 * @package SimplePortal
 *
 * @author SimplePortal Team
 * @copyright 2013 SimplePortal Team
 * @license BSD 3-clause
 *
 * @version 2.4
 */

if (!defined('ELK'))
	die('No access...');

/**
 * SimplePortal Configuration controller class.
 * This class handles the first general, blocks and articles configuration screens
 */
class ManagePortalConfig_Controller extends Action_Controller
{
	/**
	 * General settings form
	 * @var Settings_Form
	 */
	protected $_generalSettingsForm;

	/**
	 * Block settings form
	 * @var Settings_Form
	 */
	protected $_blockSettingsForm;

	/**
	 * Article settings form
	 * @var Settings_Form
	 */
	protected $_articleSettingsForm;

	/**
	 * Main dispatcher.
	 * This function checks permissions and passes control through.
	 * If the passed section is not found it shows the information page.
	 */
	public function action_index()
	{
		global $context, $txt;

		// You need to be an admin or have manage setting permissions to change anything
		if (!allowedTo('sp_admin'))
			isAllowedTo('sp_manage_settings');

		// Some helpful friends
		require_once(SUBSDIR . '/PortalAdmin.subs.php');
		require_once(SUBSDIR . '/Portal.subs.php');
		require_once(ADMINDIR . '/ManageServer.controller.php');
		loadTemplate('PortalAdmin');

		$subActions = array(
			'information' => array($this, 'action_sportal_information'),
			'generalsettings' => array($this, 'action_sportal_admin_general_settings'),
			'blocksettings' => array($this, 'action_sportal_admin_block_settings'),
			'articlesettings' => array($this, 'action_sportal_admin_article_settings'),
		);

		// Set the default to the information tab
		$subAction = isset($_REQUEST['sa']) && isset($subActions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'information';

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $txt['sp-adminConfiguration'],
			'help' => 'sp_ConfigurationArea',
			'description' => $txt['sp-adminConfigurationDesc'],
		);

		// Right then, off you go
		$action = new Action();
		$action->initialize($subActions, 'information');
		$action->dispatch($subAction);
	}

	/**
	 * General settings that control global portal actions
	 */
	public function action_sportal_admin_general_settings()
	{
		global $context, $scripturl, $txt;

		$db = database();

		$request = $db->query('', '
			SELECT id_theme, value AS name
			FROM {db_prefix}themes
			WHERE variable = {string:name}
				AND id_member = {int:member}
			ORDER BY id_theme',
			array(
				'member' => 0,
				'name' => 'name',
			)
		);
		$context['SPortal']['themes'] = array('0' => &$txt['portalthemedefault']);
		while ($row = $db->fetch_assoc($request))
			$context['SPortal']['themes'][$row['id_theme']] = $row['name'];
		$db->free_result($request);

		// initialize the form
		$this->_initGeneralSettingsForm();
		$config_vars = $this->_generalSettingsForm->settings();

		// These are very likely to come in handy! (i.e. without them we're doomed!)
		require_once(ADMINDIR . '/ManagePermissions.controller.php');
		require_once(ADMINDIR . '/ManageServer.controller.php');
		require_once(SUBSDIR . '/Settings.class.php');

		if (isset($_GET['save']))
		{
			checkSession();

			Settings_Form::save_db($config_vars);
			redirectexit('action=admin;area=portalconfig;sa=generalsettings');
		}

		$context['post_url'] = $scripturl . '?action=admin;area=portalconfig;sa=generalsettings;save';
		$context['settings_title'] = $txt['sp-adminGeneralSettingsName'];
		$context['page_title'] = $txt['sp-adminGeneralSettingsName'];
		$context['sub_template'] = 'general_settings';

		Settings_Form::prepare_db($config_vars);
	}

	/**
	 * Initialize General Settings Form.
	 * Retrieve and return the general portal settings.
	 */
	private function _initGeneralSettingsForm()
	{
		global $txt, $context;

		// instantiate the form
		$this->_generalSettingsForm = new Settings_Form();

		$config_vars = array(
			array('select', 'sp_portal_mode', explode('|', $txt['sp_portal_mode_options'])),
			array('check', 'sp_maintenance'),
			array('text', 'sp_standalone_url'),
			'',
			array('select', 'portaltheme', $context['SPortal']['themes']),
			array('check', 'sp_disableColor'),
			array('check', 'sp_disableForumRedirect'),
			array('check', 'sp_disable_random_bullets'),
			array('check', 'sp_disable_php_validation'),
			array('check', 'sp_disable_side_collapse'),
			array('check', 'sp_resize_images'),
		);

		return $this->_generalSettingsForm->settings($config_vars);
	}

	/**
	 * Settings that control how blocks behave
	 */
	public function action_sportal_admin_block_settings()
	{
		global $context, $scripturl, $txt;

		// initialize the form
		$this->_initBlockSettingsForm();
		$config_vars = $this->_blockSettingsForm->settings();

		// These are very likely to come in handy! (i.e. without them we're doomed!)
		require_once(ADMINDIR . '/ManagePermissions.controller.php');
		require_once(ADMINDIR . '/ManageServer.controller.php');
		require_once(SUBSDIR . '/Settings.class.php');

		if (isset($_GET['save']))
		{
			checkSession();

			$width_checkup = array('left', 'right');
			foreach ($width_checkup as $pos)
			{
				if (!empty($_POST[$pos . 'width']))
				{
					if (stripos($_POST[$pos . 'width'], 'px') !== false)
						$suffix = 'px';
					elseif (strpos($_POST[$pos . 'width'], '%') !== false)
						$suffix = '%';
					else
						$suffix = '';

					preg_match_all('/(?:([0-9]+)|.)/i', $_POST[$pos . 'width'], $matches);

					$number = (int) implode('', $matches[1]);
					if (!empty($number) && $number > 0)
						$_POST[$pos . 'width'] = $number . $suffix;
					else
						$_POST[$pos . 'width'] = '';
				}
				else
					$_POST[$pos . 'width'] = '';
			}

			unset($config_vars[7]);
			$config_vars = array_merge(
				$config_vars, array(
					array('check', 'sp_adminIntegrationHide'),
					array('check', 'sp_profileIntegrationHide'),
					array('check', 'sp_pmIntegrationHide'),
					array('check', 'sp_mlistIntegrationHide'),
					array('check', 'sp_searchIntegrationHide'),
					array('check', 'sp_calendarIntegrationHide'),
					array('check', 'sp_moderateIntegrationHide'),
				)
			);

			Settings_Form::save_db($config_vars);
			redirectexit('action=admin;area=portalconfig;sa=blocksettings');
		}

		$context['post_url'] = $scripturl . '?action=admin;area=portalconfig;sa=blocksettings;save';
		$context['settings_title'] = $txt['sp-adminBlockSettingsName'];
		$context['page_title'] = $txt['sp-adminBlockSettingsName'];
		$context['sub_template'] = 'general_settings';

		Settings_Form::prepare_db($config_vars);
	}

	/**
	 * Initialize Block Settings Form.
	 * Retrieve and return the general portal settings.
	 */
	private function _initBlockSettingsForm()
	{
		global $txt;

		// instantiate the block form
		$this->_blockSettingsForm = new Settings_Form();

		$config_vars = array(
			array('check', 'showleft'),
			array('check', 'showright'),
			array('text', 'leftwidth'),
			array('text', 'rightwidth'),
			'',
			array('check', 'sp_enableIntegration'),
			array('multicheck', 'sp_IntegrationHide', 'subsettings' => array('sp_adminIntegrationHide' => $txt['admin'], 'sp_profileIntegrationHide' => $txt['profile'], 'sp_pmIntegrationHide' => $txt['personal_messages'], 'sp_mlistIntegrationHide' => $txt['members_title'], 'sp_searchIntegrationHide' => $txt['search'], 'sp_calendarIntegrationHide' => $txt['calendar'], 'sp_moderateIntegrationHide' => $txt['moderate'])),
		);

		return $this->_blockSettingsForm->settings($config_vars);
	}

	/**
	 * Settings to control articles
	 */
	public function action_sportal_admin_article_settings()
	{
		global $context, $scripturl, $txt;

		// initialize the form
		$this->_initArticleSettingsForm();
		$config_vars = $this->_articleSettingsForm->settings();

		// These are very likely to come in handy! (i.e. without them we're doomed!)
		require_once(ADMINDIR . '/ManagePermissions.controller.php');
		require_once(ADMINDIR . '/ManageServer.controller.php');
		require_once(SUBSDIR . '/Settings.class.php');

		// Save away
		if (isset($_GET['save']))
		{
			checkSession();

			Settings_Form::save_db($config_vars);
			redirectexit('action=admin;area=portalconfig;sa=articlesettings');
		}

		// Show the form
		$context['post_url'] = $scripturl . '?action=admin;area=portalconfig;sa=articlesettings;save';
		$context['settings_title'] = $txt['sp-adminArticleSettingsName'];
		$context['page_title'] = $txt['sp-adminArticleSettingsName'];
		$context['sub_template'] = 'general_settings';

		Settings_Form::prepare_db($config_vars);
	}

	/**
	 * Initialize Article Settings Form.
	 * Retrieve and return the general portal settings.
	 */
	private function _initArticleSettingsForm()
	{
		// instantiate the article form
		$this->_articleSettingsForm = new Settings_Form();

		$config_vars = array(
			array('check', 'articleactive'),
			array('int', 'articleperpage'),
			array('int', 'articlelength'),
			array('check', 'articleavatar'),
		);

		return $this->_articleSettingsForm->settings($config_vars);
	}

	/**
	 * Our about page etc.
	 *
	 * @param boolean $in_admin
	 */
	public function action_sportal_information($in_admin = true)
	{
		global $context, $scripturl, $txt, $sportal_version, $user_profile;

		$context['sp_credits'] = array(
			array(
				'pretext' => $txt['sp-info_intro'],
				'title' => $txt['sp-info_team'],
				'groups' => array(
					array(
						'title' => $txt['sp-info_groups_pm'],
						'members' => array(
							'Eliana Tamerin',
							'Huw',
						),
					),
					array(
						'title' => $txt['sp-info_groups_dev'],
						'members' => array(
							'<span onclick="if (getInnerHTML(this).indexOf(\'Sinan\') == -1) setInnerHTML(this, \'Sinan &quot;[SiNaN]&quot; &Ccedil;evik\'); return false;">Selman &quot;[SiNaN]&quot; Eser</span>',
							'&#12487;&#12451;&#12531;1031',
							'Nathaniel Baxter',
						),
					),
					array(
						'title' => $txt['sp-info_groups_support'],
						'members' => array(
							'<span onclick="if (getInnerHTML(this).indexOf(\'Queen\') == -1) setInnerHTML(this, \'Angelina &quot;Queen of Support&quot; Belle\'); return false;">AngelinaBelle</span>',
						),
					),
					array(
						'title' => $txt['sp-info_groups_customize'],
						'members' => array(
							'Robbo',
							'Berat &quot;grafitus&quot; Do&#287;an',
							'Blue',
						),
					),
					array(
						'title' => $txt['sp-info_groups_language'],
						'members' => array(
							'Jade &quot;Alundra&quot; Elizabeth',
							'<span onclick="if (getInnerHTML(this).indexOf(\'King\') == -1) setInnerHTML(this, \'130 &quot;King of Pirates&quot; 860\'); return false;">130860</span>',
						),
					),
					array(
						'title' => $txt['sp-info_groups_marketing'],
						'members' => array(
							'Runic',
						),
					),
					array(
						'title' => $txt['sp-info_groups_beta'],
						'members' => array(
							'&#214;zg&#252;r',
							'Willerby',
							'David',
							'Dr. Deejay',
							'Brack1',
							'c23_Mike',
							'Underdog',
						),
					),
				),
			),
			array(
				'title' => $txt['sp-info_special'],
				'posttext' => $txt['sp-info_anyone'],
				'groups' => array(
					array(
						'title' => $txt['sp-info_groups_translators'],
						'members' => array(
							$txt['sp-info_translators_message'],
						),
					),
					array(
						'title' => $txt['sp-info_groups_founder'],
						'members' => array(
						),
					),
					array(
						'title' => $txt['sp-info_groups_orignal_pm'],
						'members' => array(
						),
					),
					array(
						'title' => $txt['sp-info_fam_fam'],
						'members' => array(
							$txt['sp-info_fam_fam_message'],
						),
					),
				),
			),
		);

		if (!$in_admin)
		{
			loadTemplate('PortalAdmin');

			$context['robot_no_index'] = true;
			$context['in_admin'] = false;
		}
		else
		{
			$context['in_admin'] = true;
			$context['sp_version'] = $sportal_version;
			$context['sp_managers'] = array();

			require_once(SUBSDIR . '/Members.subs.php');
			$manager_ids = loadMemberData(membersAllowedTo('sp_admin'), false, 'minimal');

			if ($manager_ids)
			{
				foreach ($manager_ids as $member)
					$context['sp_managers'][] = '<a href="' . $scripturl . '?action=profile;u=' . $user_profile[$member]['id_member'] . '">' . $user_profile[$member]['real_name'] . '</a>';
			}
		}

		$context['sub_template'] = 'information';
		$context['page_title'] = $txt['sp-info_title'];
	}
}