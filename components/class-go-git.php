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
	 * return the theme's git repository
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
	 */
	public function git_working_branch()
	{
		exec( 'cd ' . $this->theme_dir . '; git status', $status );
		$status = $status[0];

		return preg_replace( '/# On branch/', '', $status );
	}//end git_working_branch

	/**
	 * return the theme's working branch status
	 */
	public function git_working_branch_status()
	{
		exec( 'cd ' . $this->theme_dir . '; git status -uno', $status );
		$status = $status[1];

		return preg_replace( '/ \(use -u to show untracked files\)/', '', $status );
	}//end git_working_branch_status

	/**
	 * convert #XXX to an issue link
	 */
	public function link_issue_hash( $text, $repository )
	{
		$text = preg_replace( '/(#([0-9]+))/', '<a href="https://github.com/' . $repository . '/issues/\2" class="issue">\1</a>', $text );

		return $text;
	}//end link_issue_hash

	/**
	 * return commit HTML
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
				Branch: <span class="branch"><?php echo $this->git_working_branch(); ?></span> on <a href="https://github.com/<?php echo $repository; ?>"><?php echo $repository; ?></a>
				<div class="status">
					<?php echo $this->git_working_branch_status(); ?>
				</div>
			</div>
			<?php echo $formatted; ?>
		</div>
		<?php
	}//end theme_page
}//end class
