<?php

/* ***PERMISSIONS***
 * If permissions are NOT set, the forum will not be displayed. Bellow are the defualt set of permissions and groups for a forum.
*
* GROUPS
* group_id     Description
* 1            Guests
* 2            Registered
* 3            Registered_coppa   
* 4            Global_moderators
* 5            Admin
* 6            Bots
* 7            Newly_registered   
*
* AUTH
* auth_role_id     Description
* 15               STANDARD
* 16               NOACCESS
* 17               READONLY
* 18               LIMITED
* 19               BOT
* 20               ONQUEUE
* 21               POLLS
* 22               LIMITED_POLLS
* 24               NEW_MEMBER
*/


/**
 * Create a forum
*
* Inspiration: http://www.phpbb.com/community/viewtopic.php?f=71&t=758985
* Author: Michael Fairchild <mfairchild365@gmail.com>
*
* Eample:
* create_forum(array('name' => 'Cool Test', 'parent_id' => 1));
*
* @param array $options        An array of options for the forum.  Including 'name' and 'parent_id'.
* @param array $permission     An array of permissions for the forum.
*
* @return int  - The forum ID.
*/
function create_forum($options = array('name' => 'default', 'parent_id' => 0, 'forum_type' => 1), $permissions = false)
{
	global $db, $auth;

	if (!isset($options['parent_id'])) {
		$options['parent_id'] = 0;
	}
	//forum type: 1 = forum, 0 = category.
	if (!isset($options['forum_type'])) {
		$options['forum_type'] = 1;
	}

	$forum_data = array(
			'parent_id'                => $options['parent_id'],
			'left_id'                  => 0,
			'right_id'                 => 0,
			'forum_parents'            => '',
			'forum_name'               => $options['name'],
			'forum_desc'               => '',
			'forum_desc_bitfield'      => '',
			'forum_desc_options'       => 7,
			'forum_desc_uid'           => '',
			'forum_link'               => '',
			'forum_password'           => '',
			'forum_style'              => 0,
			'forum_image'              => '',
			'forum_rules'              => '',
			'forum_rules_link'         => '',
			'forum_rules_bitfield'     => '',
			'forum_rules_options'      => 7,
			'forum_rules_uid'          => '',
			'forum_topics_per_page'    => 0,
			'forum_type'               => $options['forum_type'],
			'forum_status'             => 0,
			'forum_posts'              => 0,
			'forum_topics'             => 0,
			'forum_topics_real'        => 0,
			'forum_last_post_id'       => 0,
			'forum_last_poster_id'     => 0,
			'forum_last_post_subject'  => '',
			'forum_last_post_time'     => 0,
			'forum_last_poster_name'   => '',
			'forum_last_poster_colour' => '',
			'forum_flags'              => 32,
			'display_on_index'         => true,
			'enable_indexing'          => true,
			'enable_icons'             => false,
			'enable_prune'             => false,
			'prune_next'               => 0,
			'prune_days'               => 0,
			'prune_viewed'             => 0,
			'prune_freq'               => 0,
	);

	if (!class_exists('acp_forums'))
	{
		global $phpbb_root_path, $phpEx;
		include($phpbb_root_path . 'includes/acp/acp_forums.' . $phpEx);
	}

	$forums_admin = new acp_forums();

	//update_forum_data will return only errors.  If success, there will be no return data.
	if ($error = $forums_admin->update_forum_data($forum_data)) {
		;
		return false;
	}

	//Set the permissions
	if ($permissions == false) {
		$permissions = Array(
				//guests
				Array('group_id' => 1,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 17,  //READ ONLY
						'auth_setting' => 0 ),
				//Registered
				Array('group_id' => 2,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 15,  //STANDARD
						'auth_setting' => 0 ),
				//Registered_coppa
				Array('group_id' => 3,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 15,  //STANDARD
						'auth_setting' => 0 ),
				//Global_moderators
				Array('group_id' => 4,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 21,  //POLLS
						'auth_setting' => 0 ),
				//Admin
				Array('group_id' => 5,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 14,  //FULL
						'auth_setting' => 0 ),
				Array('group_id' => 5,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 10,  //MOD_FULL
						'auth_setting' => 0 ),
				//Bots
				Array('group_id' => 6,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 19,  //BOT
						'auth_setting' => 0 ),
				//Newly_registered
				Array('group_id' => 7,
						'forum_id' => $forum_data['forum_id'],
						'auth_option_id' => 0,
						'auth_role_id' => 24, //NEW_MEMBER
						'auth_setting' => 0 ),
		);
	}

	$auth->acl_clear_prefetch();

	// Now insert the data
	if (!$db->sql_multi_insert(ACL_GROUPS_TABLE, $permissions)) {
		return false;
	}

	return $forum_data['forum_id'];
}

/**
 * Note: function taken from the phpBB project. Parts of this code rely on this function. Do not remove it.
 * Checks whatever or not a variable is OK for use in the Database
 * param mixed $value_ary An array of the form array(array('lang' => ..., 'value' => ..., 'column_type' =>))'
 * param mixed $error The error array
 */
function validate_range($value_ary, &$error)
{
	global $user;

	$column_types = array(
			'BOOL'  => array('php_type' => 'int',       'min' => 0,                 'max' => 1),
			'USINT' => array('php_type' => 'int',       'min' => 0,                 'max' => 65535),
			'UINT'  => array('php_type' => 'int',       'min' => 0,                 'max' => (int) 0x7fffffff),
			'INT'   => array('php_type' => 'int',       'min' => (int) 0x80000000,  'max' => (int) 0x7fffffff),
			'TINT'  => array('php_type' => 'int',       'min' => -128,              'max' => 127),

			'VCHAR' => array('php_type' => 'string',    'min' => 0,                 'max' => 255),
	);
	foreach ($value_ary as $value)
	{
		$column = explode(':', $value['column_type']);
		$max = $min = 0;
		$type = 0;
		if (!isset($column_types[$column[0]]))
		{
			continue;
		}
		else
		{
			$type = $column_types[$column[0]];
		}

		switch ($type['php_type'])
		{
			case 'string' :
				$max = (isset($column[1])) ? min($column[1],$type['max']) : $type['max'];
				if (strlen($value['value']) > $max)
				{
					$error[] = sprintf($user->lang['SETTING_TOO_LONG'], $user->lang[$value['lang']], $max);
				}
				break;

			case 'int':
				$min = (isset($column[1])) ? max($column[1],$type['min']) : $type['min'];
				$max = (isset($column[2])) ? min($column[2],$type['max']) : $type['max'];
				if ($value['value'] < $min)
				{
					$error[] = sprintf($user->lang['SETTING_TOO_LOW'], $user->lang[$value['lang']], $min);
				}
				else if ($value['value'] > $max)
				{
					$error[] = sprintf($user->lang['SETTING_TOO_BIG'], $user->lang[$value['lang']], $max);
				}
				break;
		}
	}
}