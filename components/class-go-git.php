<?php

class GO_Git
{
	public $theme_dir = null;

	/**
	 * constructor
	 */
	public function __construct()
	{
		$this->theme_dir = get_stylesheet_directory();

		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}//end __construct

	/**
	 * add an admin menu
	 */
	public function admin_menu()
	{
		add_theme_page( 'Theme Git Info', 'Theme Git Info', 'edit_posts', 'go-git', array( $this, 'theme_page' ) );
	}//end admin_menu

	/**
	 * enqueue some scripts on the admin page
	 */
	public function admin_enqueue_scripts()
	{
		wp_register_style( 'go-git', plugins_url( 'components/css/go-git.css', __DIR__ ), array(), 1 );
		wp_enqueue_style( 'go-git' );
	}//end admin_enqueue_scripts

	/**
	 * return array of git remotes
	 *
	 * @return $remotes array
	 */
	public function git_remotes()
	{
		exec( 'cd ' . $this->theme_dir . '; git remote', $remotes );

		return $remotes;
	}//end git_remotes

	/**
	 * return the theme's git repository
	 *
	 * @return $remote theme
	 */
	public function git_repository()
	{
		exec( 'cd ' . $this->theme_dir . '; git remote -v', $remotes );

		preg_match( '#origin[\s\t]+git@github.com\:([^\.]+)#', $remotes[0], $matches );

		$remote = $matches[1];

		return $remote;
	}//end git_repository

	/**
	 * return the theme's working branch
	 *
	 * @return working branch of theme
	 */
	public function git_working_branch()
	{
		exec( 'cd ' . $this->theme_dir . '; git status', $status );
		$status = $status[0];

		return preg_replace( '/# On branch/', '', $status );
	}//end git_working_branch

	/**
	 * return the theme's working branch status
	 *
	 * @return $status of working branch
	 */
	public function git_working_branch_status()
	{
		$remotes = $this->git_remotes();

		$status = array();

		foreach( $remotes as $remote )
		{
			exec( __DIR__ . "/git-log {$this->theme_dir} {$remote} '>'", $ahead );
			exec( __DIR__ . "/git-log {$this->theme_dir} {$remote} '<'", $behind );

			$status[ $remote ]['ahead'] = (int) $ahead[0];
			$status[ $remote ]['behind'] = (int) $behind[0];
		}//end foreach

		return $status;
	}//end git_working_branch_status

	/**
	 * convert #XXX to an issue link
	 *
	 * @param $text to convert
	 * @param $repository to access
	 * @return $text issue link
	 */
	public function link_issue_hash( $text, $repository )
	{
		$text = preg_replace( '/(#([0-9]+))/', '<a href="https://github.com/' . $repository . '/issues/\2" class="issue">\1</a>', $text );

		return $text;
	}//end link_issue_hash

	/**
	 * return commit HTML
	 *
	 * @param $args arguements to commit
	 * @return ob_get_clean() cleaned commit HTML
	 */
	public function output_commit( $args )
	{
		extract( $args );

		ob_start();
		?>
			<div class="commit">
				<header>
				<h1>Commit by <span class="author"><?php echo $author; ?></span> at <time><?php echo human_time_diff( strtotime( $date ), time() ); ?> ago</time> (<a href="https://github.com/<?php echo $repository; ?>/commit/<?php echo $commit; ?>" class="diff">view diff</a>)</h1>
				</header>
				<div class="body">
					<?php
						$body = htmlentities( $body );
						echo nl2br( $this->link_issue_hash( $body, $repository ) );
					?>
				</div>
		</div>
		<?php

		return ob_get_clean();
	}//end output_commit

	/**
	 * admin page
	 */
	public function theme_page()
	{
		$repository = $this->git_repository();

		exec( 'cd ' . $this->theme_dir . '; git log --no-merges --decorate=full -n 20 ', $output );

		$formatted = '';
		$commit = array();

		foreach( $output as $line )
		{
			if ( preg_match( '/^commit ([^ ]+)/', $line, $matches ) )
			{
				if ( $commit )
				{
					$formatted .= $this->output_commit( $commit );
				}//end if

				$commit = array(
					'commit' => $matches[1],
					'repository' => $repository,
				);
			}//end if
			elseif ( preg_match( '/^Author: ([^<]+)/', $line, $matches ) )
			{
				$commit['author'] = trim( $matches[1] );
			}//end elseif
			elseif ( preg_match( '/^Date: (.+)$/', $line, $matches ) )
			{
				$commit['date'] = $matches[1];
			}//end elseif
			elseif ( preg_match( '/^Merge: (.+)$/', $line, $matches ) )
			{
				$commit['merge'] = $matches[1];
			}//end elseif
			else
			{
				$commit['body'] .= $line . "\n";
			}//end else
		}//end foreach

		$formatted .= $this->output_commit( $commit );

		?>
		<div class="wrap go-git">
			<?php screen_icon('options-general'); ?>
			<h2>Theme Git Info</h2>
			<div class="current-branch">
				<h3>Branch: <span class="branch"><?php echo $this->git_working_branch(); ?></span> on <a href="https://github.com/<?php echo $repository; ?>"><?php echo $repository; ?></a></h3>
				<div class="status">
					<?php
						$statuses = $this->git_working_branch_status();

						$final_status = '';
						foreach ( $statuses as $remote => $status )
						{
							if ( ! $status['behind'] && ! $status['ahead'] )
							{
								continue;
							}//end if

							if ( $status['behind'] )
							{
								$final_status .= $status['behind'] . ' commit' . ($status['behind'] > 1 ? 's' : '') . ' behind ' . $remote . '. ';
							}//end if

							if ( $status['ahead'] )
							{
								$final_status .= $status['ahead'] . ' commit' . ($status['ahead'] > 1 ? 's' : '') . ' ahead of ' . $remote . '. ';
							}//end if
						}//end foreach

						if ( ! $final_status )
						{
							$final_status = 'This branch is in sync with all of its remotes.';
						}//end if

						echo $final_status;
					?>
				</div>
			</div>
			<?php echo $formatted; ?>
		</div>
		<?php
	}//end theme_page
}//end class
