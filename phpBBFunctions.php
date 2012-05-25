<?php

/**
 * Create a new category or forum on the fly
*/
function createPost ($forum,$msgSubject,$msgText) {

  // note that multibyte support is enabled here 
  $my_subject   = utf8_normalize_nfc($msgSubject); // request_var('subject', '', true) was in place of $msgSubject


  $my_text   = utf8_normalize_nfc($msgText); // request_var('my_text', '', true) was in place of $msgText

  // variables to hold the parameters for submit_post
  $poll = $uid = $bitfield = $options = ''; 

  generate_text_for_storage($my_subject, $uid, $bitfield, $options, false, false, false);

  generate_text_for_storage($my_text, $uid, $bitfield, $options, true, true, true);

  $data = array( 
    'forum_id'      => $forum,
    'icon_id'      => false,

    'enable_bbcode'      => true,
    'enable_smilies'   => true,
    'enable_urls'      => true,
    'enable_sig'      => true,

    'message'      => $my_text,
    'message_md5'   => md5($my_text),
            
    'bbcode_bitfield'   => $bitfield,
    'bbcode_uid'      => $uid,

    'post_edit_locked'   => 0,
    'topic_title'      => $my_subject,
    'notify_set'      => false,
    'notify'         => false,
    'post_time'       => 0,
    'forum_name'      => '',
    'enable_indexing'   => true,
  );

  submit_post('post', $my_subject, '', POST_NORMAL, $poll, $data);

  return $data['post_id'];

}

/**
 * Create a new category or forum on the fly
*/
function createCategoryForum($parent_id,$forum_perm_from,$forumType,$forumName,$forumDesc)
{

  global $db, $config, $auth, $phpEx, $phpbb_root_path, $cache;

  // The action. Always add when called in here
  $u_action = 'add';

  // The following code has been taken from around line 115 in includes/acp/acp_forums.php

  $forum_data = $errors = array();
    
  $forum_data += array(
    'parent_id'            => $parent_id,
    'forum_type'         => $forumType,
    'type_action'         => '',
    'forum_status'         => ITEM_UNLOCKED,
    'forum_parents'         => '',
    'forum_name'         => utf8_normalize_nfc($forumName),
    'forum_link'         => '',
    'forum_link_track'      => false,
    'forum_desc'         => utf8_normalize_nfc($forumDesc),
    'forum_desc_uid'      => '',
    'forum_desc_options'   => 7,
    'forum_desc_bitfield'   => '',
    'forum_rules'         => '',
    'forum_rules_uid'      => '',
    'forum_rules_options'   => 7,
    'forum_rules_bitfield'   => '',
    'forum_rules_link'      => '',
    'forum_image'         => '',
    'forum_style'         => 0,
    'display_subforum_list'   => false,
    'display_on_index'      => false,
    'forum_topics_per_page'   => 0,
    'enable_indexing'      => true,
    'enable_icons'         => false,
    'enable_prune'         => false,
    'enable_post_review'   => true,
    'prune_days'         => 7,
    'prune_viewed'         => 7,
    'prune_freq'         => 1,
    'prune_old_polls'      => false,
    'prune_announce'      => false,
    'prune_sticky'         => false,
    'forum_password'      => '',
    'forum_password_confirm'=> '',
    'forum_password_unset'   => false,
  );

  // Use link_display_on_index setting if forum type is link
  if ($forum_data['forum_type'] == FORUM_LINK)
  {
    $forum_data['display_on_index'] = false;
    // Linked forums are not able to be locked...
    $forum_data['forum_status'] = ITEM_UNLOCKED;
  }

  $forum_data['show_active'] = ($forum_data['forum_type'] == FORUM_POST) ? false : false;

  // Get data for forum rules if specified...
  if ($forum_data['forum_rules'])
  {
    generate_text_for_storage($forum_data['forum_rules'], $forum_data['forum_rules_uid'], $forum_data['forum_rules_bitfield'], $forum_data['forum_rules_options'], false, false, false);
  }

  // Get data for forum description if specified
  if ($forum_data['forum_desc'])
  {
    generate_text_for_storage($forum_data['forum_desc'], $forum_data['forum_desc_uid'], $forum_data['forum_desc_bitfield'], $forum_data['forum_desc_options'], false, false, false);
  }

  $errors = update_forum_data($forum_data);

  if (!sizeof($errors))
  {

    // Copy permissions? // if statement removed below so permissions are copied

    // From the mysql documentation:
    // Prior to MySQL 4.0.14, the target table of the INSERT statement cannot appear in the FROM clause of the SELECT part of the query. This limitation is lifted in 4.0.14.
    // Due to this we stay on the safe side if we do the insertion "the manual way"

    // Copy permisisons from/to the acl users table (only forum_id gets changed)
    $sql = 'SELECT user_id, auth_option_id, auth_role_id, auth_setting
      FROM ' . ACL_USERS_TABLE . '
      WHERE forum_id = ' . $forum_perm_from;
    $result = $db->sql_query($sql);

    $users_sql_ary = array();
    while ($row = $db->sql_fetchrow($result))
    {
      $users_sql_ary[] = array(
        'user_id'         => (int) $row['user_id'],
        'forum_id'         => (int) $forum_data['forum_id'],
        'auth_option_id'   => (int) $row['auth_option_id'],
        'auth_role_id'      => (int) $row['auth_role_id'],
        'auth_setting'      => (int) $row['auth_setting']
      );
    }
    $db->sql_freeresult($result);

    // Copy permisisons from/to the acl groups table (only forum_id gets changed)
    $sql = 'SELECT group_id, auth_option_id, auth_role_id, auth_setting
      FROM ' . ACL_GROUPS_TABLE . '
      WHERE forum_id = ' . $forum_perm_from;
    $result = $db->sql_query($sql);

    $groups_sql_ary = array();
    while ($row = $db->sql_fetchrow($result))
    {
      $groups_sql_ary[] = array(
        'group_id'         => (int) $row['group_id'],
        'forum_id'         => (int) $forum_data['forum_id'],
        'auth_option_id'   => (int) $row['auth_option_id'],
        'auth_role_id'      => (int) $row['auth_role_id'],
        'auth_setting'      => (int) $row['auth_setting']
      );
    }
    $db->sql_freeresult($result);

    // Now insert the data
    $db->sql_multi_insert(ACL_USERS_TABLE, $users_sql_ary);
    $db->sql_multi_insert(ACL_GROUPS_TABLE, $groups_sql_ary);
    cache_moderators();
    // removed } from here that was related to permissions if above iain

    $auth->acl_clear_prefetch();
    $cache->destroy('sql', FORUMS_TABLE);

    return $forum_data['forum_id'];

  }  

  else
  {
    echo "Error!!" . print_r($errors);

    return 0;
  }

}

// The following function has been taken from acp_forums.php file / acp_forums class and no changes have been made by me!

   /**
   * Update forum data
   */
   function update_forum_data(&$forum_data)
   {
      global $db, $user, $cache;

      $errors = array();

      if (!$forum_data['forum_name'])
      {
         $errors[] = $user->lang['FORUM_NAME_EMPTY'];
      }

      if (utf8_strlen($forum_data['forum_desc']) > 4000)
      {
         $errors[] = $user->lang['FORUM_DESC_TOO_LONG'];
      }

      if (utf8_strlen($forum_data['forum_rules']) > 4000)
      {
         $errors[] = $user->lang['FORUM_RULES_TOO_LONG'];
      }

      if ($forum_data['forum_password'] || $forum_data['forum_password_confirm'])
      {
         if ($forum_data['forum_password'] != $forum_data['forum_password_confirm'])
         {
            $forum_data['forum_password'] = $forum_data['forum_password_confirm'] = '';
            $errors[] = $user->lang['FORUM_PASSWORD_MISMATCH'];
         }
      }

      if ($forum_data['prune_days'] < 0 || $forum_data['prune_viewed'] < 0 || $forum_data['prune_freq'] < 0)
      {
         $forum_data['prune_days'] = $forum_data['prune_viewed'] = $forum_data['prune_freq'] = 0;
         $errors[] = $user->lang['FORUM_DATA_NEGATIVE'];
      }

      $range_test_ary = array(
         array('lang' => 'FORUM_TOPICS_PAGE', 'value' => $forum_data['forum_topics_per_page'], 'column_type' => 'TINT:0'),
      );
      validate_range($range_test_ary, $errors);



      // Set forum flags
      // 1 = link tracking
      // 2 = prune old polls
      // 4 = prune announcements
      // 8 = prune stickies
      // 16 = show active topics
      // 32 = enable post review
      $forum_data['forum_flags'] = 0;
      $forum_data['forum_flags'] += ($forum_data['forum_link_track']) ? FORUM_FLAG_LINK_TRACK : 0;
      $forum_data['forum_flags'] += ($forum_data['prune_old_polls']) ? FORUM_FLAG_PRUNE_POLL : 0;
      $forum_data['forum_flags'] += ($forum_data['prune_announce']) ? FORUM_FLAG_PRUNE_ANNOUNCE : 0;
      $forum_data['forum_flags'] += ($forum_data['prune_sticky']) ? FORUM_FLAG_PRUNE_STICKY : 0;
      $forum_data['forum_flags'] += ($forum_data['show_active']) ? FORUM_FLAG_ACTIVE_TOPICS : 0;
      $forum_data['forum_flags'] += ($forum_data['enable_post_review']) ? FORUM_FLAG_POST_REVIEW : 0;

      // Unset data that are not database fields
      $forum_data_sql = $forum_data;

      unset($forum_data_sql['forum_link_track']);
      unset($forum_data_sql['prune_old_polls']);
      unset($forum_data_sql['prune_announce']);
      unset($forum_data_sql['prune_sticky']);
      unset($forum_data_sql['show_active']);
      unset($forum_data_sql['enable_post_review']);
      unset($forum_data_sql['forum_password_confirm']);

      // What are we going to do tonight Brain? The same thing we do everynight,
      // try to take over the world ... or decide whether to continue update
      // and if so, whether it's a new forum/cat/link or an existing one
      if (sizeof($errors))
      {
         return $errors;
      }

      // As we don't know the old password, it's kinda tricky to detect changes
      if ($forum_data_sql['forum_password_unset'])
      {
         $forum_data_sql['forum_password'] = '';
      }
      else if (empty($forum_data_sql['forum_password']))
      {
         unset($forum_data_sql['forum_password']);
      }
      else
      {
         $forum_data_sql['forum_password'] = phpbb_hash($forum_data_sql['forum_password']);
      }
      unset($forum_data_sql['forum_password_unset']);

      if (!isset($forum_data_sql['forum_id']))
      {
         // no forum_id means we're creating a new forum
         unset($forum_data_sql['type_action']);

         if ($forum_data_sql['parent_id'])
         {
            $sql = 'SELECT left_id, right_id, forum_type
               FROM ' . FORUMS_TABLE . '
               WHERE forum_id = ' . $forum_data_sql['parent_id'];
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);

            if (!$row)
            {
               trigger_error($user->lang['PARENT_NOT_EXIST'] . "id = $parent_id", E_USER_WARNING);
            }

            if ($row['forum_type'] == FORUM_LINK)
            {
               $errors[] = $user->lang['PARENT_IS_LINK_FORUM'];
               return $errors;
            }

            $sql = 'UPDATE ' . FORUMS_TABLE . '
               SET left_id = left_id + 2, right_id = right_id + 2
               WHERE left_id > ' . $row['right_id'];
            $db->sql_query($sql);

            $sql = 'UPDATE ' . FORUMS_TABLE . '
               SET right_id = right_id + 2
               WHERE ' . $row['left_id'] . ' BETWEEN left_id AND right_id';
            $db->sql_query($sql);

            $forum_data_sql['left_id'] = $row['right_id'];
            $forum_data_sql['right_id'] = $row['right_id'] + 1;
         }
         else
         {
            $sql = 'SELECT MAX(right_id) AS right_id
               FROM ' . FORUMS_TABLE;
            $result = $db->sql_query($sql);
            $row = $db->sql_fetchrow($result);
            $db->sql_freeresult($result);

            $forum_data_sql['left_id'] = $row['right_id'] + 1;
            $forum_data_sql['right_id'] = $row['right_id'] + 2;
         }

         $sql = 'INSERT INTO ' . FORUMS_TABLE . ' ' . $db->sql_build_array('INSERT', $forum_data_sql);
         $db->sql_query($sql);

         $forum_data['forum_id'] = $db->sql_nextid();

         add_log('admin', 'LOG_FORUM_ADD', $forum_data['forum_name']);
      }
      else
      {
         $row = get_forum_info($forum_data_sql['forum_id']);

         if ($row['forum_type'] == FORUM_POST && $row['forum_type'] != $forum_data_sql['forum_type'])
         {
            // Has subforums and want to change into a link?
            if ($row['right_id'] - $row['left_id'] > 1 && $forum_data_sql['forum_type'] == FORUM_LINK)
            {
               $errors[] = $user->lang['FORUM_WITH_SUBFORUMS_NOT_TO_LINK'];
               return $errors;
            }

            // we're turning a postable forum into a non-postable forum
            if ($forum_data_sql['type_action'] == 'move')
            {
               $to_forum_id = request_var('to_forum_id', 0);

               if ($to_forum_id)
               {
                  $errors = $this->move_forum_content($forum_data_sql['forum_id'], $to_forum_id);
               }
               else
               {
                  return array($user->lang['NO_DESTINATION_FORUM']);
               }
            }
            else if ($forum_data_sql['type_action'] == 'delete')
            {
               $errors = $this->delete_forum_content($forum_data_sql['forum_id']);
            }
            else
            {
               return array($user->lang['NO_FORUM_ACTION']);
            }

            $forum_data_sql['forum_posts'] = $forum_data_sql['forum_topics'] = $forum_data_sql['forum_topics_real'] = $forum_data_sql['forum_last_post_id'] = $forum_data_sql['forum_last_poster_id'] = $forum_data_sql['forum_last_post_time'] = 0;
            $forum_data_sql['forum_last_poster_name'] = $forum_data_sql['forum_last_poster_colour'] = '';
         }
         else if ($row['forum_type'] == FORUM_CAT && $forum_data_sql['forum_type'] == FORUM_LINK)
         {
            // Has subforums?
            if ($row['right_id'] - $row['left_id'] > 1)
            {
               // We are turning a category into a link - but need to decide what to do with the subforums.
               $action_subforums = request_var('action_subforums', '');
               $subforums_to_id = request_var('subforums_to_id', 0);

               if ($action_subforums == 'delete')
               {
                  $rows = get_forum_branch($row['forum_id'], 'children', 'descending', false);

                  foreach ($rows as $_row)
                  {
                     // Do not remove the forum id we are about to change. ;)
                     if ($_row['forum_id'] == $row['forum_id'])
                     {
                        continue;
                     }

                     $forum_ids[] = $_row['forum_id'];
                     $errors = array_merge($errors, $this->delete_forum_content($_row['forum_id']));
                  }

                  if (sizeof($errors))
                  {
                     return $errors;
                  }

                  if (sizeof($forum_ids))
                  {
                     $sql = 'DELETE FROM ' . FORUMS_TABLE . '
                        WHERE ' . $db->sql_in_set('forum_id', $forum_ids);
                     $db->sql_query($sql);

                     $sql = 'DELETE FROM ' . ACL_GROUPS_TABLE . '
                        WHERE ' . $db->sql_in_set('forum_id', $forum_ids);
                     $db->sql_query($sql);

                     $sql = 'DELETE FROM ' . ACL_USERS_TABLE . '
                        WHERE ' . $db->sql_in_set('forum_id', $forum_ids);
                     $db->sql_query($sql);

                     // Delete forum ids from extension groups table
                     $sql = 'SELECT group_id, allowed_forums
                        FROM ' . EXTENSION_GROUPS_TABLE;
                     $result = $db->sql_query($sql);

                     while ($_row = $db->sql_fetchrow($result))
                     {
                        if (!$_row['allowed_forums'])
                        {
                           continue;
                        }

                        $allowed_forums = unserialize(trim($_row['allowed_forums']));
                        $allowed_forums = array_diff($allowed_forums, $forum_ids);

                        $sql = 'UPDATE ' . EXTENSION_GROUPS_TABLE . "
                           SET allowed_forums = '" . ((sizeof($allowed_forums)) ? serialize($allowed_forums) : '') . "'
                           WHERE group_id = {$_row['group_id']}";
                        $db->sql_query($sql);
                     }
                     $db->sql_freeresult($result);

                     $cache->destroy('_extensions');
                  }
               }
               else if ($action_subforums == 'move')
               {
                  if (!$subforums_to_id)
                  {
                     return array($user->lang['NO_DESTINATION_FORUM']);
                  }

                  $sql = 'SELECT forum_name
                     FROM ' . FORUMS_TABLE . '
                     WHERE forum_id = ' . $subforums_to_id;
                  $result = $db->sql_query($sql);
                  $_row = $db->sql_fetchrow($result);
                  $db->sql_freeresult($result);

                  if (!$_row)
                  {
                     return array($user->lang['NO_FORUM']);
                  }

                  $subforums_to_name = $_row['forum_name'];

                  $sql = 'SELECT forum_id
                     FROM ' . FORUMS_TABLE . "
                     WHERE parent_id = {$row['forum_id']}";
                  $result = $db->sql_query($sql);

                  while ($_row = $db->sql_fetchrow($result))
                  {
                     $this->move_forum($_row['forum_id'], $subforums_to_id);
                  }
                  $db->sql_freeresult($result);

                  $sql = 'UPDATE ' . FORUMS_TABLE . "
                     SET parent_id = $subforums_to_id
                     WHERE parent_id = {$row['forum_id']}";
                  $db->sql_query($sql);
               }

               // Adjust the left/right id
               $sql = 'UPDATE ' . FORUMS_TABLE . '
                  SET right_id = left_id + 1
                  WHERE forum_id = ' . $row['forum_id'];
               $db->sql_query($sql);
            }
         }
         else if ($row['forum_type'] == FORUM_CAT && $forum_data_sql['forum_type'] == FORUM_POST)
         {
            // Changing a category to a forum? Reset the data (you can't post directly in a cat, you must use a forum)
            $forum_data_sql['forum_posts'] = 0;
            $forum_data_sql['forum_topics'] = 0;
            $forum_data_sql['forum_topics_real'] = 0;
            $forum_data_sql['forum_last_post_id'] = 0;
            $forum_data_sql['forum_last_post_subject'] = '';
            $forum_data_sql['forum_last_post_time'] = 0;
            $forum_data_sql['forum_last_poster_id'] = 0;
            $forum_data_sql['forum_last_poster_name'] = '';
            $forum_data_sql['forum_last_poster_colour'] = '';
         }

         if (sizeof($errors))
         {
            return $errors;
         }

         if ($row['parent_id'] != $forum_data_sql['parent_id'])
         {
            $errors = $this->move_forum($forum_data_sql['forum_id'], $forum_data_sql['parent_id']);
         }

         if (sizeof($errors))
         {
            return $errors;
         }

         unset($forum_data_sql['type_action']);

         if ($row['forum_name'] != $forum_data_sql['forum_name'])
         {
            // the forum name has changed, clear the parents list of all forums (for safety)
            $sql = 'UPDATE ' . FORUMS_TABLE . "
               SET forum_parents = ''";
            $db->sql_query($sql);
         }

         // Setting the forum id to the forum id is not really received well by some dbs. ;)
         $forum_id = $forum_data_sql['forum_id'];
         unset($forum_data_sql['forum_id']);

         $sql = 'UPDATE ' . FORUMS_TABLE . '
            SET ' . $db->sql_build_array('UPDATE', $forum_data_sql) . '
            WHERE forum_id = ' . $forum_id;
         $db->sql_query($sql);

         // Add it back
         $forum_data['forum_id'] = $forum_id;

         add_log('admin', 'LOG_FORUM_EDIT', $forum_data['forum_name']);
      }

      return $errors;
   }


// The function below has been taken from adm/index.php and hasn't been changed either. Iain

/**
* Checks whatever or not a variable is OK for use in the Database
* param mixed $value_ary An array of the form array(array('lang' => ..., 'value' => ..., 'column_type' =>))'
* param mixed $error The error array
*/
function validate_range($value_ary, &$error)
{
   global $user;
   
   $column_types = array(
      'BOOL'   => array('php_type' => 'int',       'min' => 0,             'max' => 1),
      'USINT'   => array('php_type' => 'int',      'min' => 0,             'max' => 65535),
      'UINT'   => array('php_type' => 'int',       'min' => 0,             'max' => (int) 0x7fffffff),
      'INT'   => array('php_type' => 'int',       'min' => (int) 0x80000000,    'max' => (int) 0x7fffffff),
      'TINT'   => array('php_type' => 'int',      'min' => -128,            'max' => 127),
      
      'VCHAR'   => array('php_type' => 'string',    'min' => 0,             'max' => 255),
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
function validate_range1($value_ary, &$error)
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
?>