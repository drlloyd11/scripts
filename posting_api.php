	<?php
	
	
	class posting_api
	{
		/*
		topic table:
		topic_id
		forum_id
		topic_title
		topic_status
		topic_type
		topic_shadow_id
	
		sec:
		topic_posts
		topic_shadow_posts
		topic_deleted_posts
		topic_unapproved_posts
	
		all must be approved:
		topic_first_username
		topic_first_user_id
		topic_last_post_id
		topic_last_username
		topic_last_user_id
		topic_last_post_title
		topic_last_post_time
		*/
	
		/*
		post table:
		post_id
		topic_id
		post_subject
		post_body
		post_status
		post_type
		username
		user_id
		forum_id
		post_time
		
		/*
		forum table:
		forum_id
		forum_posts
		forum_shadow_posts
		forum_deleted_posts
		forum_unapproved_posts
	
		sec:
		forum_topics
		forum_shadow_topics
		forum_deleted_topics
		forum_unapproved_topics
	
		forum_last_username
		forum_last_user_id
		forum_last_post_title
		forum_last_post_time
		*/
	
		const NORMAL = 0;
	
		// status
		const UNAPPROVED = 1;
		const DELETED = 2;
	
		// type
		const ANNOUNCEMENT = 1; 
		const STICKY = 2;
	
		// we, for now, only support the insertion of posts that are not already
		// shadow'd and not deleted (it must first exist for it to be shadow'd or deleted!)
		static function insert_topic($data)
		{
			global $db;
	
			// one transaction, we can now garuntee that atomicity of insertions
			$db->sql_transaction('begin');
	
			$user_id = (int) $data['user_id'];
			$forum_id = (int) $data['forum_id'];
			$topic_title = $data['title'];
			$post_contents = $data['post_contents'];
			$topic_status = (int) $data['status'];
			$topic_type = (int) $data['type'];
			$approved = (bool) $data['approved'];
	
			if (isset($shadow_forums) && is_array($shadow_forums))
			{
				$shadow_forums = array_map('intval', $data['shadow_forums']);
			}
			else
			{
				$shadow_forums = array();
			}
	
			$time = (isset($data['time'])) ? (int) $data['time'] : time();
	
			if (isset($data['username']))
			{
				$username = $data['username'];
			}
			else
			{
				$sql = 'SELECT username
					FROM ' . USERS_TABLE . '
					WHERE user_id = ' . $user_id;
				$result = $db->sql_query($sql);
				$username = (string) $db->sql_fetchfield('username');
				$db->sql_freeresult($result);
			}
	
			$sql = 'SELECT forum_topics, forum_unapproved_topics, forum_posts, forum_unapproved_posts
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
	
			// throw our topic to the dogs
			$topic_data = array(
				'topic_title'				=> $topic_title,
				'topic_status'				=> $topic_status,
				'topic_type'				=> $topic_type,
				'topic_posts'				=> 1,
				'topic_shadow_posts'		=> 0,
				'topic_deleted_posts'		=> 0,
				'topic_unapproved_posts'	=> ($approved) ? 0 : 1,
				'topic_first_username'		=> $username,
				'topic_first_user_id'		=> $user_id,
				'topic_last_username'		=> $username,
				'topic_last_post_title'		=> $topic_title,
				'topic_last_post_time'		=> $time,
				'topic_last_user_id'		=> $user_id,
				'forum_id'					=> $forum_id
			);
	
			$db->sql_handle_data('INSERT', TOPICS_TABLE, $topic_data);
			$topic_id = (int) $db->sql_nextid();
	
			// I suppose it is time to make us a post, no?
			$post_data = array(
				'topic_id'		=> $topic_id,
				'post_subject'	=> $topic_title,
				'post_body'		=> $post_contents,
				'username'		=> $username,
				'user_id'		=> $user_id,
				'post_status'	=> $topic_status, // first post inherits its type from the topic
				'post_type'		=> self::NORMAL, // for now, there are no shadow, global or sticky posts
				'forum_id'		=> $forum_id,
				'post_time'		=> $time,
			);
	
			$db->sql_handle_data('INSERT', POSTS_TABLE, $post_data);
			$post_id = (int) $db->sql_nextid();
	
			// time to fill in the blanks
			$db->sql_handle_data('UPDATE', TOPICS_TABLE, array('topic_first_post_id' => $post_id, 'topic_last_post_id' => $post_id), "topic_id = $topic_id");
	
			// let's go update the forum table
			$forum_data = array(
				'forum_posts'	=> ++$row['forum_posts'],
				'forum_topics'	=> ++$row['forum_topics'],
			);
	
			// the last post inserted is always the latest,
			// we must update the forum records to make sure everybody knows the good news
			if ($approved)
			{
				$forum_data['forum_last_username']		= $username;
				$forum_data['forum_last_user_id']		= $user_id;
				$forum_data['forum_last_post_title']	= $topic_title;
				$forum_data['forum_last_post_time']		= $time;
				$forum_data['forum_last_post_id']		= $post_id;
			}
			else
			{
				$forum_data['forum_unapproved_posts']	= ++$row['forum_unapproved_posts'];
				$forum_data['forum_unapproved_topics']	= ++$row['forum_unapproved_topics'];
			}
	
			$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_data, "forum_id = $forum_id");
	
			foreach ($shadow_forums as $shadow_forum_id)
			{
				$data['shadow_topic_id'] = $topic_id;
				$data['forum_id'] = $shadow_forum_id;
				self::insert_shadow_topic($data);
			}
	
			// we are consistant, victory is ours
			$db->sql_transaction('commit');
	
			return $topic_id;
		}
	
		// inserts a shadow topic into the database
		static function insert_shadow_topic($data)
		{
			global $db;
	
			// one transaction, we can now garuntee that atomicity of insertions
			$db->sql_transaction('begin');
	
			$forum_id = (int) $data['forum_id'];
			$shadow_topic_id = (int) $data['shadow_topic_id'];
	
			$sql = 'SELECT forum_posts, forum_unapproved_posts
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id;
			$result = $db->sql_query($sql);
			$forum_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
	
			$sql = 'SELECT topic_posts, topic_unapproved_posts
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id;
			$result = $db->sql_query($sql);
			$topic_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
	
			// set us up some arrays
			$forum_data = array(
				'forum_topics'			=> ++$row['forum_topics'],
				'forum_shadow_topics'	=> ++$row['forum_shadow_topics']
			);
			$topic_data = array(
				'forum_id'			=> $forum_id,
				'topic_shadow_id'	=> $shadow_topic_id
			);
	
			if ($approved)
			{
	/*			$topic_data['topic_first_username'] = $topic_data['topic_last_username']		= $username;
				$topic_data['topic_first_user_id'] = $topic_data['topic_last_user_id']			= $user_id;
				$topic_data['topic_first_post_time'] = $topic_data['topic_last_post_time']		= $time;
				$topic_data['topic_first_post_id'] = $topic_data['topic_last_post_id']			= $post_id;
	
				$forum_data['forum_last_username']		= $username;
				$forum_data['forum_last_user_id']		= $user_id;
				$forum_data['forum_last_post_title']	= $post_title;
				$forum_data['forum_last_post_time']		= $time;
				$forum_data['forum_last_post_id']		= $post_id;*/
			}
			else
			{
				$forum_data['forum_unapproved_topics']	= ++$topic_row['forum_unapproved_topics'];
			}
	
			// update the crap
			$db->sql_handle_data('INSERT', TOPICS_TABLE, $topic_data);
			$topic_id = (int) $db->sql_nextid();
			$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_data, "forum_id = $forum_id");
	
			// we are consistant, victory is ours
			$db->sql_transaction('commit');
	
			return $topic_id;
		}
	
		static function insert_post($data)
		{
			global $db;
			// one transaction, we can now garuntee that atomicity of insertions
			$db->sql_transaction('begin');
	
			$user_id = (int) $data['user_id'];
			$topic_id = (int) $data['topic_id'];
	
			// begin massive amounts of hand holding
	
			if (isset($data['forum_id']))
			{
				$forum_id = (int) $data['forum_id'];
			}
			else
			{
				$sql = 'SELECT forum_id
					FROM ' . TOPICS_TABLE . '
					WHERE topic_id = ' . $topic_id;
				$result = $db->sql_query($sql);
				$forum_id = (int) $db->sql_fetchfield('forum_id');
				$db->sql_freeresult($result);
			}
	
			$post_title = $data['title'];
			$post_contents = $data['post_contents'];
			$time = (isset($data['time'])) ? (int) $data['time'] : time();
	
			if (isset($data['username']))
			{
				$username = $data['username'];
			}
			else
			{
				$sql = 'SELECT username
					FROM ' . USERS_TABLE . '
					WHERE user_id = ' . $user_id;
				$result = $db->sql_query($sql);
				$username = (string) $db->sql_fetchfield('username');
				$db->sql_freeresult($result);
			}
	
			$post_status = (int) $data['status'];
			$approved = ($post_status & self::NORMAL) === self::NORMAL;
	
			// I suppose it is time to make us a post, no?
			$post_data = array(
				'topic_id'		=> $topic_id,
				'post_subject'	=> $post_title,
				'post_body'		=> $post_contents,
				'username'		=> $username,
				'user_id'		=> $user_id,
				'post_status'	=> $post_status,
				'forum_id'		=> $forum_id,
				'post_time'		=> $time,
			);
			$db->sql_handle_data('INSERT', POSTS_TABLE, $post_data);
	
			// what is the new post_id?
			$post_id = (int) $db->sql_nextid();
	
			$sql = 'SELECT forum_posts, forum_unapproved_posts
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . (int) $forum_id;
			$result = $db->sql_query($sql);
			$forum_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
	
			$sql = 'SELECT topic_posts, topic_unapproved_posts
				FROM ' . TOPICS_TABLE . '
				WHERE topic_id = ' . (int) $topic_id;
			$result = $db->sql_query($sql);
			$topic_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
	
			// set us up some arrays
			$forum_data = array();
			$topic_data = array();
	
			if ($approved)
			{
				$topic_data['topic_last_username']		= $username;
				$topic_data['topic_last_user_id']		= $user_id;
				$topic_data['topic_last_post_title']	= $post_title;
				$topic_data['topic_last_post_time']		= $time;
				$topic_data['topic_last_post_id']		= $post_id;
	
				$forum_data['forum_last_username']		= $username;
				$forum_data['forum_last_user_id']		= $user_id;
				$forum_data['forum_last_post_title']	= $post_title;
				$forum_data['forum_last_post_time']		= $time;
				$forum_data['forum_last_post_id']		= $post_id;
	
	/*			$db->sql_handle_data('UPDATE', TOPICS_TABLE, $topic_data, "topic_shadow_id = $topic_id");
	
				$sql = 'SELECT forum_id
					FROM ' . TOPICS_TABLE . '
					WHERE topic_shadow_id = ' . $topic_id;
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_data, "forum_id = {$row['forum_id']}");
				}
				$db->sql_freeresult($result);*/
			}
			else
			{
				$topic_data['topic_unapproved_posts']	= ++$topic_row['topic_unapproved_posts'];
				$forum_data['forum_unapproved_posts']	= ++$forum_row['forum_unapproved_posts'];
			}
	
			$topic_data['topic_posts']	= ++$topic_row['topic_posts'];
			$forum_data['forum_posts']	= ++$forum_row['forum_posts'];
	
			// update the crap
			$db->sql_handle_data('UPDATE', TOPICS_TABLE, $topic_data, "topic_id = $topic_id");
			$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_data, "forum_id = $forum_id");
	
			// we are consistant, victory is ours
			$db->sql_transaction('commit');
	
			return $post_id;
		}
	
		static function delete_topic($data)
		{
			// just call the more powerful variant with just one topic :)
			self::delete_topics(array('topic_ids' => array($data['topic_id'])));
		}
	
		static function delete_topics($data)
		{
			global $db;
	
			// lets get this party started
			$db->sql_transaction('begin');
	
			$topic_ids = array_map('intval', $data['topic_ids']);
	
			// what kind of topic is this? lets find out how much we must tamper with the forum table...
			// TODO: investigate how aggregate functions can speed this up/reduce the number of results returned/misc. other benefits
			$sql = 'SELECT topic_posts, topic_shadow_posts, topic_deleted_posts, topic_unapproved_posts, topic_shadow_id, forum_id, topic_status
				FROM ' . TOPICS_TABLE . '
				WHERE ' . $db->sql_in_set('topic_id', $topic_ids);
			$result = $db->sql_query($sql);
			// the following in an array, key'd by forum id that refers to topic rows
			$forum_lookup = array();
			while ($topic_row = $db->sql_fetchrow($result))
			{
				$forum_id = (int) $topic_row['forum_id'];
	
				if (!isset($forum_lookup[$forum_id]))
				{
					$forum_lookup[$forum_id] = array();
					$forum_lookup[$forum_id]['forum_posts'] = 0;
					$forum_lookup[$forum_id]['forum_shadow_posts'] = 0;
					$forum_lookup[$forum_id]['forum_deleted_posts'] = 0;
					$forum_lookup[$forum_id]['forum_unapproved_posts'] = 0;
	
					$forum_lookup[$forum_id]['forum_topics'] = 0;
					$forum_lookup[$forum_id]['forum_shadow_topics'] = 0;
					$forum_lookup[$forum_id]['forum_deleted_topics'] = 0;
					$forum_lookup[$forum_id]['forum_unapproved_topics'] = 0;
				}
	
				// deal with posts
				$forum_lookup[$forum_id]['forum_posts'] += $topic_row['topic_posts'];
				$forum_lookup[$forum_id]['forum_shadow_posts'] += $topic_row['topic_shadow_posts'];
				$forum_lookup[$forum_id]['forum_deleted_posts'] += $topic_row['topic_deleted_posts'];
				$forum_lookup[$forum_id]['forum_unapproved_posts'] += $topic_row['topic_unapproved_posts'];
	
				// deal with topics
				$topic_status = (int) $topic_row['topic_status'];
				++$forum_lookup[$forum_id]['forum_topics']; // a topic is a topic
				$forum_lookup[$forum_id]['forum_shadow_topics'] += ($topic_row['topic_shadow_id'] != 0);
				$forum_lookup[$forum_id]['forum_deleted_topics'] += ($topic_status & self::DELETED) === self::DELETED;
				$forum_lookup[$forum_id]['forum_unapproved_topics'] += ($topic_status & self::UNAPPROVED) === self::UNAPPROVED;
			}
			$db->sql_freeresult($result);
	
			// goodnight, topics
			$db->sql_query('DELETE FROM ' . TOPICS_TABLE . ' WHERE ' . $db->sql_in_set('topic_id', $topic_ids));
	
			// goodnight, posts
			$db->sql_query('DELETE FROM ' . POSTS_TABLE . ' WHERE ' . $db->sql_in_set('topic_id', $topic_ids));
	
			$forum_ids = array_keys($forum_lookup);
	
			// what kind of topic is this? lets find out how much we must tamper with the forum table...
			$sql = 'SELECT forum_posts, forum_shadow_posts, forum_deleted_posts, forum_unapproved_posts, forum_id, forum_topics, forum_shadow_topics, forum_deleted_topics, forum_unapproved_topics
				FROM ' . FORUMS_TABLE . '
				WHERE ' . $db->sql_in_set('forum_id', $forum_ids);
			$result = $db->sql_query($sql);
			$forum_rows = array();
			while ($forum_row = $db->sql_fetchrow($result))
			{
				$forum_id = (int) $forum_row['forum_id'];
				$forum_rows[$forum_id] = $forum_row;
			}
			$db->sql_freeresult($result);
	
			$shadow_topic_ids = array();
			foreach ($forum_rows as $forum_id => $forum_row)
			{
				$topic_row = $forum_lookup[$forum_id];
				$forum_array = array(
					'forum_posts'				=> max($forum_row['forum_posts'] - $topic_row['forum_posts'], 0),
					'forum_shadow_posts'		=> max($forum_row['forum_shadow_posts'] - $topic_row['forum_shadow_posts'], 0),
					'forum_deleted_posts'		=> max($forum_row['forum_deleted_posts'] - $topic_row['forum_deleted_posts'], 0),
					'forum_unapproved_posts'	=> max($forum_row['forum_unapproved_posts'] - $topic_row['forum_unapproved_posts'], 0),
	
					'forum_topics'				=> max($forum_row['forum_topics'] - $topic_row['forum_topics'], 0),
					'forum_shadow_topics'		=> max($forum_row['forum_shadow_topics'] - $topic_row['forum_shadow_topics'], 0),
					'forum_deleted_topics'		=> max($forum_row['forum_deleted_topics'] - $topic_row['forum_deleted_topics'], 0),
					'forum_unapproved_topics'	=> max($forum_row['forum_unapproved_topics'] - $topic_row['forum_unapproved_topics'], 0),
				);
	
				assert($forum_row['forum_posts'] - $topic_row['forum_posts'] >= 0);
				assert($forum_row['forum_shadow_posts'] - $topic_row['forum_shadow_posts'] >= 0);
				assert($forum_row['forum_deleted_posts'] - $topic_row['forum_deleted_posts'] >= 0);
				assert($forum_row['forum_unapproved_posts'] - $topic_row['forum_unapproved_posts'] >= 0);
	
				assert($forum_row['forum_topics'] - $topic_row['forum_topics'] >= 0);
				assert($forum_row['forum_shadow_topics'] - $topic_row['forum_shadow_topics'] >= 0);
				assert($forum_row['forum_deleted_topics'] - $topic_row['forum_deleted_topics'] >= 0);
				assert($forum_row['forum_unapproved_topics'] - $topic_row['forum_unapproved_topics'] >= 0);
	
					// get the last "normal" post in the forum, we _must_ update it
				$sql = 'SELECT MAX(post_id) as max_post_id
					FROM ' . POSTS_TABLE . '
					WHERE post_status = ' . self::NORMAL . '
						AND forum_id = ' . $forum_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
	
				// anything left?
				if ($row)
				{
					// OK, lets go do some magick
					$sql = 'SELECT username, user_id, post_subject, post_time
						FROM '. POSTS_TABLE . '
						WHERE post_id = ' . (int) $row['max_post_id'];
					$result = $db->sql_query($sql);
					$last_post = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
	
					$forum_array['forum_last_user_id'] = (int) $last_post['user_id'];
					$forum_array['forum_last_username'] = $last_post['username'];
					$forum_array['forum_last_post_title'] = $last_post['post_subject'];
					$forum_array['forum_last_post_time'] = (int) $last_post['post_time'];
					$forum_array['forum_last_post_id'] = (int) $row['max_post_id'];
				}
				else
				{
					// reset forum state
					$forum_array['forum_last_user_id'] = 0;
					$forum_array['forum_last_username'] = '';
					$forum_array['forum_last_post_title'] = '';
					$forum_array['forum_last_post_time'] = 0;
					$forum_array['forum_last_post_id'] = 0;
				}
	
				$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_array, "forum_id = $forum_id");	
			}
	
			// let's not get too hasty, we can kill off the shadows later,
			// instead we compose a list of all shadows and then efficiently kill them off :)
			// @TODO: we can just merge the given topic ids with all of their shadows, more efficient, more concise
			$sql = 'SELECT topic_id
				FROM ' . TOPICS_TABLE . '
				WHERE ' . $db->sql_in_set('topic_shadow_id', $topic_ids);
			$result = $db->sql_query($sql);
	
			while ($row = $db->sql_fetchrow($result))
			{
				$shadow_topic_ids[] = $row['topic_id'];
			}
	
			$db->sql_freeresult($result);
	
			// recursion, the other white meat.
			if (sizeof($shadow_topic_ids))
			{
				self::delete_topics(array('topic_ids' => $shadow_topic_ids));
			}
	
			// goodnight, moon
			$db->sql_transaction('commit');
		}
	
		static function delete_post($data)
		{
			// just call the more powerful variant with just one post :)
			self::delete_posts(array('post_ids' => array($data['post_id'])));
		}
	
		static function delete_posts($data)
		{
			global $db;
	
			// lets get this party started
			$db->sql_transaction('begin');
	
			$post_ids = array_map('intval', $data['post_ids']);
	
			$sql = 'SELECT topic_id, post_status, post_id, post_shadow_id, forum_id
				FROM ' . POSTS_TABLE . '
				WHERE ' . $db->sql_in_set('post_id', $post_ids);
			$result = $db->sql_query($sql);
	
			// the following arrays are designed to allow for much faster updates
			$topic_lookup = array();
			$forum_lookup = array();
	
			while ($post_row = $db->sql_fetchrow($result))
			{
				$topic_id = (int) $post_row['topic_id'];
				$forum_id = (int) $post_row['forum_id'];
				$post_status = (int) $post_row['post_status'];
	
				if (!isset($topic_lookup[$topic_id]))
				{
					$topic_lookup[$topic_id] = array();
					$topic_lookup[$topic_id]['topic_posts'] = 0;
					$topic_lookup[$topic_id]['topic_shadow_posts'] = 0; // did we just try to kill a shadow post?!
					$topic_lookup[$topic_id]['topic_deleted_posts'] = 0;
					$topic_lookup[$topic_id]['topic_unapproved_posts'] = 0;
				}
	
				if (!isset($forum_lookup[$forum_id]))
				{
					$forum_lookup[$forum_id] = array();
					$forum_lookup[$forum_id]['forum_posts'] = 0;
					$forum_lookup[$forum_id]['forum_shadow_posts'] = 0;
					$forum_lookup[$forum_id]['forum_deleted_posts'] = 0;
					$forum_lookup[$forum_id]['forum_unapproved_posts'] = 0;
				}
	
				++$topic_lookup[$topic_id]['topic_posts']; // we remove a post, go figure
				$topic_lookup[$topic_id]['topic_shadow_posts'] += ($post_row['post_shadow_id'] != 0); // did we just try to kill a shadow post?!
				$topic_lookup[$topic_id]['topic_deleted_posts'] += ($post_status & self::DELETED) === self::DELETED;
				$topic_lookup[$topic_id]['topic_unapproved_posts'] += ($post_status & self::UNAPPROVED) === self::UNAPPROVED;
	
				++$forum_lookup[$forum_id]['forum_posts'];
				$forum_lookup[$forum_id]['forum_shadow_posts'] += ($post_row['post_shadow_id'] != 0); // did we just try to kill a shadow post?!
				$forum_lookup[$forum_id]['forum_deleted_posts'] += ($post_status & self::DELETED) === self::DELETED;
				$forum_lookup[$forum_id]['forum_unapproved_posts'] += ($post_status & self::UNAPPROVED) === self::UNAPPROVED;
			}
			$db->sql_freeresult($result);
	
			$topic_ids = array_keys($topic_lookup);
			// goodnight, posts
			$db->sql_query('DELETE FROM ' . POSTS_TABLE . ' WHERE ' . $db->sql_in_set('post_id', $post_ids));
	
			// mangle the topics table now :)
			$sql = 'SELECT topic_posts, topic_shadow_posts, topic_deleted_posts, topic_unapproved_posts, topic_id, topic_status
				FROM ' . TOPICS_TABLE . '
				WHERE ' . $db->sql_in_set('topic_id', $topic_ids);
			$result = $db->sql_query($sql);
			$topic_rows = array();
			while ($topic_row = $db->sql_fetchrow($result))
			{
				$topic_id = (int) $topic_row['topic_id'];
				$topic_rows[$topic_id] = $topic_row;
			}
			$db->sql_freeresult($result);
	
			$empty_topic_ids = array();
			foreach ($topic_rows as $topic_id => $topic_row)
			{
				$post_row = $topic_lookup[$topic_id];
				$topic_array = array(
					'topic_posts'				=> max($topic_row['topic_posts'] - $post_row['topic_posts'], 0),
					'topic_shadow_posts'		=> max($topic_row['topic_shadow_posts'] - $post_row['topic_shadow_posts'], 0),
					'topic_deleted_posts'		=> max($topic_row['topic_deleted_posts'] - $post_row['topic_deleted_posts'], 0),
					'topic_unapproved_posts'	=> max($topic_row['topic_unapproved_posts'] - $post_row['topic_unapproved_posts'], 0),
				);
	
				assert($topic_row['topic_posts'] - $post_row['topic_posts'] >= 0);
				assert($topic_row['topic_shadow_posts'] - $post_row['topic_shadow_posts'] >= 0);
				assert($topic_row['topic_deleted_posts'] - $post_row['topic_deleted_posts'] >= 0);
				assert($topic_row['topic_unapproved_posts'] - $post_row['topic_unapproved_posts'] >= 0);
	
				// get the first post, we have to do some more work :/
				$sql = 'SELECT MIN(post_id) as min_post_id
					FROM ' . POSTS_TABLE . '
					WHERE topic_id = ' . $topic_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if ($row)
				{
					// so here is the deal:
					// we have at least one post left in this topic...
					// this means that we must
					// a) determine whether the first post is "visible", as the topic
					//    should inherit the first post's status
					// b) update the last post status of the topic
					$topic_status = (int) $topic_row['topic_status'];
	
					$sql = 'SELECT post_status, username, user_id, post_subject, post_time
						FROM ' . POSTS_TABLE . '
						WHERE post_id = ' . (int) $row['min_post_id'];
					$result = $db->sql_query($sql);
					$first_post = $db->sql_fetchrow($result);
					$db->sql_fetchrow($result);
	
					$post_status = (int) $first_post['post_status'];
	
					$topic_status |= $post_status & self::DELETED;
					$topic_status |= $post_status & self::UNAPPROVED;
	
					$topic_array['topic_status'] = $topic_status;
	
					// update the topic!
					if (($post_status & self::NORMAL) === self::NORMAL)
					{
						// OK, lets go do some magick
						$topic_array['topic_first_user_id'] = (int) $first_post['user_id'];
						$topic_array['topic_first_username'] = $first_post['username'];
						$topic_array['topic_first_post_time'] = (int) $first_post['post_time'];
						$topic_array['topic_first_post_id'] = (int) $row['min_post_id'];
					}
					else
					{
						$topic_array['topic_first_user_id'] = 0;
						$topic_array['topic_first_username'] = '';
						$topic_array['topic_first_post_time'] = 0;
						$topic_array['topic_first_post_id'] = 0;
					}
	
					// get the last "normal" post in the topic, we _must_ update it
					$sql = 'SELECT MAX(post_id) as max_post_id
						FROM ' . POSTS_TABLE . '
						WHERE post_status = ' . self::NORMAL . '
							AND topic_id = ' . $topic_id;
					$result = $db->sql_query($sql);
					$row = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
	
					// anything left?
					if ($row)
					{
						// OK, lets go do some magick
						$sql = 'SELECT username, user_id, post_subject, post_time
							FROM '. POSTS_TABLE . '
							WHERE post_id = ' . (int) $row['max_post_id'];
						$result = $db->sql_query($sql);
						$last_post = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
	
						$topic_array['topic_last_user_id'] = (int) $last_post['user_id'];
						$topic_array['topic_last_username'] = $last_post['username'];
						$topic_array['topic_last_post_title'] = $last_post['post_subject'];
						$topic_array['topic_last_post_time'] = (int) $last_post['post_time'];
						$topic_array['topic_last_post_id'] = (int) $row['max_post_id'];
					}
					else
					{
						$topic_array['topic_last_user_id'] = 0;
						$topic_array['topic_last_username'] = '';
						$topic_array['topic_last_post_title'] = '';
						$topic_array['topic_last_post_time'] = 0;
						$topic_array['topic_last_post_id'] = 0;
					}
	
					$db->sql_handle_data('UPDATE', TOPICS_TABLE, $topic_array, "topic_id = $topic_id");
				}
				else
				{
					// mark this topic for execution!
					$empty_topic_ids[] = $topic_id;
				}
			}
	
			// mangle the forums table, takes advantage of topic table mangling
			$sql = 'SELECT forum_posts, forum_shadow_posts, forum_deleted_posts, forum_unapproved_posts, forum_id
				FROM ' . FORUMS_TABLE . '
				WHERE forum_id = ' . $forum_id;
			$result = $db->sql_query($sql);
			$forum_rows = array();
			while ($forum_row = $db->sql_fetchrow($result))
			{
				$forum_id = (int) $forum_row['forum_id'];
				$forum_rows[$forum_id] = $forum_row;
			}
			$db->sql_freeresult($result);
	
			$shadow_topic_ids = array();
			foreach ($forum_rows as $forum_id => $forum_row)
			{
				$topic_row = $forum_lookup[$forum_id];
				$forum_array = array(
					'forum_posts'				=> max($forum_row['forum_posts'] - $topic_row['forum_posts'], 0),
					'forum_shadow_posts'		=> max($forum_row['forum_shadow_posts'] - $topic_row['forum_shadow_posts'], 0),
					'forum_deleted_posts'		=> max($forum_row['forum_deleted_posts'] - $topic_row['forum_deleted_posts'], 0),
					'forum_unapproved_posts'	=> max($forum_row['forum_unapproved_posts'] - $topic_row['forum_unapproved_posts'], 0),
				);
	
				assert($forum_row['forum_posts'] - $topic_row['forum_posts'] >= 0);
				assert($forum_row['forum_shadow_posts'] - $topic_row['forum_shadow_posts'] >= 0);
				assert($forum_row['forum_deleted_posts'] - $topic_row['forum_deleted_posts'] >= 0);
				assert($forum_row['forum_unapproved_posts'] - $topic_row['forum_unapproved_posts'] >= 0);
	
				// get the last "normal" post in the forum, we _must_ update it
				$sql = 'SELECT MAX(topic_last_post_id) as max_post_id
					FROM ' . TOPICS_TABLE . '
					WHERE topic_status = ' . self::NORMAL . '
						AND forum_id = ' . $forum_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
	
				// anything left?
				if ($row)
				{
					// OK, lets go do some magick
					$sql = 'SELECT username, user_id, post_subject, post_time
						FROM '. POSTS_TABLE . '
						WHERE post_id = ' . (int) $row['max_post_id'];
					$result = $db->sql_query($sql);
					$last_post = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
	
					$forum_array['forum_last_user_id'] = (int) $last_post['user_id'];
					$forum_array['forum_last_username'] = $last_post['username'];
					$forum_array['forum_last_post_title'] = $last_post['post_subject'];
					$forum_array['forum_last_post_time'] = (int) $last_post['post_time'];
					$forum_array['forum_last_post_id'] = (int) $row['max_post_id'];
				}
				else
				{
					// reset forum state
					$forum_array['forum_last_user_id'] = 0;
					$forum_array['forum_last_username'] = '';
					$forum_array['forum_last_post_title'] = '';
					$forum_array['forum_last_post_time'] = 0;
					$forum_array['forum_last_post_id'] = 0;
				}
				$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_array, "forum_id = $forum_id");
			}
	
			$shadow_post_ids = array();
	
			// let's not get too hasty, we can kill off the shadows later,
			// instead we compose a list of all shadows and then efficiently kill them off :)
			// @TODO: we can just merge the given post ids with all of their shadows, more efficient, more concise
			$sql = 'SELECT post_id
				FROM ' . POSTS_TABLE . '
				WHERE ' . $db->sql_in_set('post_shadow_id', $topic_ids);
			$result = $db->sql_query($sql);
	
			while ($row = $db->sql_fetchrow($result))
			{
				$shadow_post_ids[] = $row['post_id'];
			}
	
			$db->sql_freeresult($result);
	
			// recursion, the other white meat.
			if (sizeof($shadow_topic_ids))
			{
				self::delete_posts(array('post_ids' => $shadow_post_ids));
			}
	
			// we killed all the posts in a topic, time to kill the topics!
			if (sizeof($empty_topic_ids))
			{
				self::delete_topics(array('topic_ids' => $empty_topic_ids));
			}
	
			// goodnight, moon
			$db->sql_transaction('commit');
		}
	
		static function move_topic($data)
		{
			self::move_topics(array('topic_forum_mapping' => array(array('topic_id' => $data['topic_id'], 'forum_id' => $data['forum_id'], 'make_shadow' => $data['make_shadow']))));
		}
	
		static function move_topics($data)
		{
			global $db;
	
			// lets get this party started
			$db->transaction('begin');
	
			// all of each are indexed by topic id
			$to_forum_ids = $shadow_topic_ids = array();
	
			foreach ($data['topic_forum_mapping'] as $mapping)
			{
				$topic_id = (int) $mapping['topic_id'];
				$to_forum_ids[$topic_id] = (int) $mapping['forum_id'];
				if ($mapping['make_shadow'])
				{
					$shadow_topic_ids[] = $topic_id;
				}
			}
	
			$forum_columns = array('posts', 'shadow_posts', 'deleted_posts', 'unapproved_posts', 'topics', 'shadow_topics', 'deleted_topics', 'unapproved_topics');
	
			$topic_ids = array_keys($to_forum_ids);
	
			// let us first determine how many items we are removing from the pool
			$sql = 'SELECT topic_posts, topic_shadow_posts, topic_deleted_posts, topic_unapproved_posts, forum_id, topic_status, topic_type, topic_shadow_id, topic_id
				FROM ' . TOPICS_TABLE . '
				WHERE ' . $db->sql_in_set('topic_id', $topic_ids);
			$result = $db->sql_query($sql);
			$forum_lookup = array();
			while ($topic_row = $db->sql_fetchrow($result))
			{
				$topic_id = $topic_row['topic_id'];
				$from_forum_id = (int) $topic_row['forum_id'];
				$to_forum_id = $to_forum_ids[$topic_id];
	
				// we are iterating one topic at a time...
				$forum_lookup[$from_forum_id]['forum_topics'] = $forum_lookup[$to_forum_id]['forum_topics'] = 1;
	
				foreach ($forum_columns as $column)
				{
					$forum_lookup[$from_forum_id]['forum_' . $column]	-= $topic_row['topic_posts'];
					$forum_lookup[$to_forum_id]['forum_' . $column]		+= $topic_row['topic_posts'];
				}
			}
			$db->sql_freeresult($result);
	
			// determine the totals
			$sql = 'SELECT forum_posts, forum_shadow_posts, forum_deleted_posts, forum_unapproved_posts, forum_id, forum_topics, forum_deleted_topics, forum_unapproved_topics
				FROM ' . FORUMS_TABLE . '
				WHERE ' . $db->sql_in_set('forum_id', array_keys($forum_lookup));
			$result = $db->sql_query($sql);
	
			while ($row = $db->sql_fetchrow($result))
			{
				$forum_id = (int) $row['forum_id'];
	
				foreach ($forum_columns as $column)
				{
					$forum_lookup[$forum_id][$column] += (int) $row[$column];
				}
			}
	
			// create a listing of which topics are going in which forums
			$update_list = array();
	
			foreach ($to_forum_ids as $topic_id => $forum_id)
			{
				$update_list[$forum_id][] = $topic_id;
			}
	
			// we save as many queries as we can by updating all similar topics at once
			foreach ($update_list as $forum_id => $topic_ids)
			{
				// update the topic itself
				$db->sql_handle_data('UPDATE', TOPICS_TABLE, array('forum_id' => $to_forum_id), $db->sql_in_set('topic_id', $topic_ids));
	
				// update the posts now
				$db->sql_handle_data('UPDATE', POSTS_TABLE, array('forum_id' => $to_forum_id), $db->sql_in_set('topic_id', $topic_ids));
			}
	
			// start building the needed arrays for updating the forum data
			foreach ($forum_lookup as $forum_id => $forum_data)
			{
				foreach ($forum_columns as $column)
				{
					$forum_data[$column] = max($forum_data[$column], 0); // ensure the result is unsigned
					assert($forum_data[$column] >= 0);
				}
	
				// get the last "normal" post in the old forum, we _must_ update it
				$sql = 'SELECT MAX(post_id) as max_post_id
					FROM ' . POSTS_TABLE . '
					WHERE post_status = ' . self::NORMAL . '
						AND forum_id = ' . $forum_id;
				$result = $db->sql_query($sql);
				$row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
	
				// anything left?
				if ($row)
				{
					// OK, lets go do some magick
					$sql = 'SELECT username, user_id, post_subject, post_time
						FROM '. POSTS_TABLE . '
						WHERE post_id = ' . (int) $row['max_post_id'];
					$result = $db->sql_query($sql);
					$last_post = $db->sql_fetchrow($result);
					$db->sql_freeresult($result);
	
					$forum_data['forum_last_user_id'] = (int) $last_post['user_id'];
					$forum_data['forum_last_username'] = $last_post['username'];
					$forum_data['forum_last_post_title'] = $last_post['post_subject'];
					$forum_data['forum_last_post_time'] = (int) $last_post['post_time'];
					$forum_data['forum_last_post_id'] = (int) $row['max_post_id'];
				}
				else
				{
					// reset forum state
					$forum_data['forum_last_user_id'] = 0;
					$forum_data['forum_last_username'] = '';
					$forum_data['forum_last_post_title'] = '';
					$forum_data['forum_last_post_time'] = 0;
					$forum_data['forum_last_post_id'] = 0;
				}
	
				// update the old forum
				$db->sql_handle_data('UPDATE', FORUMS_TABLE, $forum_data, "forum_id = $forum_id");
			}
	
			// hooray for code reuse!
			foreach ($shadow_topic_ids as $topic_id)
			{
				$data['shadow_topic_id'] = $topic_id;
				$data['forum_id'] = $from_forum_id;
				self::insert_shadow_topic($data);
			}
	
			$db->sql_transaction('commit');
	
		}
	}
	
	?>